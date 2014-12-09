<?php
/*
 * Copyright © 2014, Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * Except as contained in this notice, the name of the ROADIZ shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from Ambroise Maupate and Julien Blanchet.
 *
 *
 * @file NodesUtilsController.php
 * @author Thomas Aufresne
 */

namespace Themes\Rozier\Controllers;

use RZ\Roadiz\Core\Serializers\NodeJsonSerializer;
use Themes\Rozier\RozierApp;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * {@inheritdoc}
 */
class NodesUtilsController extends RozierApp
{

    /**
     * Export a Node in a Json file (.rzn).
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int                                      $nodeId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function exportAction(Request $request, $nodeId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_NODES');

        $existingNode = $this->getService('em')
                              ->find('RZ\Roadiz\Core\Entities\Node', (int) $nodeId);
        $this->getService('em')->refresh($existingNode);
        $node = NodeJsonSerializer::serialize(array($existingNode));

        $response =  new Response(
            $node,
            Response::HTTP_OK,
            array()
        );

        $response->headers->set(
            'Content-Disposition',
            $response->headers->makeDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                'node-' . $existingNode->getNodeName() . '-' . date("YmdHis")  . '.rzn'
            )
        ); // Rezo-Zero Type

        $response->prepare($request);

        return $response;
    }

    /**
     * Export all Node in a Json file (.rzn).
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function exportAllAction(Request $request)
    {
        $this->validateAccessForRole('ROLE_ACCESS_NODES');

        $existingNodes = $this->getService('em')
                              ->getRepository('RZ\Roadiz\Core\Entities\Node')
                              ->findBy(array("parent"=>null));

        foreach ($existingNodes as $existingNode) {
            $this->getService('em')->refresh($existingNode);
        }

        $node = NodeJsonSerializer::serialize($existingNodes);

        $response =  new Response(
            $node,
            Response::HTTP_OK,
            array()
        );

        $response->headers->set(
            'Content-Disposition',
            $response->headers->makeDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                'node-all-' . date("YmdHis")  . '.rzn'
            )
        ); // Rezo-Zero Type

        $response->prepare($request);

        return $response;
    }

    /**
     * Duplicate node by ID
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int                                      $nodeId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function duplicateAction(Request $request, $nodeId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_NODES');

        try {

            $existingNode = $this->getService('em')
                                  ->find('RZ\Roadiz\Core\Entities\Node', (int) $nodeId);
            $newNode = $existingNode->getHandler()->duplicate();

            $msg = $this->getTranslator()->trans("duplicated.node.%name%", array(
                '%name%' => $existingNode->getNodeName()
            ));

            $request->getSession()->getFlashBag()->add('confirm', $msg);
            $this->getService('logger')->info($msg);

            $response = new RedirectResponse(
                $this->getService('urlGenerator')
                    ->generate(
                        'nodesEditPage',
                        array("nodeId" => $newNode->getId())
                    )
            );

        } catch (\Exception $e) {

            $request->getSession()->getFlashBag()->add(
                'error',
                $this->getTranslator()->trans("impossible.duplicate.node.%name%", array(
                    '%name%' => $existingNode->getNodeName()
                ))
            );
            $request->getSession()->getFlashBag()->add('error', $e->getMessage());

            $response = new RedirectResponse(
                $this->getService('urlGenerator')
                    ->generate(
                        'nodesEditPage',
                        array("nodeId" => $existingNode->getId())
                    )
            );
        } finally {
            $response->prepare($request);
            return $response->send();
        }
    }
}
