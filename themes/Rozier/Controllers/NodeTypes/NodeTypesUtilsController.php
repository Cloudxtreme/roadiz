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
 * @file NodeTypesUtilsController.php
 * @author Ambroise Maupate
 */

namespace Themes\Rozier\Controllers\NodeTypes;

use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Serializers\NodeTypeJsonSerializer;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Themes\Rozier\RozierApp;

/**
 * {@inheritdoc}
 */
class NodeTypesUtilsController extends RozierApp
{
    /**
     * Export a Json file containing NodeType datas and fields.
     *
     * @param Request $request
     * @param int     $nodeTypeId
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function exportJsonFileAction(Request $request, $nodeTypeId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_NODETYPES');

        $nodeType = $this->get('em')
                         ->find('RZ\Roadiz\Core\Entities\NodeType', (int) $nodeTypeId);

        $serializer = new NodeTypeJsonSerializer();

        $response = new Response(
            $serializer->serialize($nodeType),
            Response::HTTP_OK,
            []
        );

        $response->headers->set(
            'Content-Disposition',
            $response->headers->makeDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                $nodeType->getName() . '.rzt'
            )
        ); // Rezo-Zero Type
        $response->prepare($request);

        return $response;
    }

    /**
     * @param Request $request
     * @return BinaryFileResponse
     */
    public function exportAllAction(Request $request)
    {
        $this->validateAccessForRole('ROLE_ACCESS_NODETYPES');

        $nodeTypes = $this->get('em')
            ->getRepository('RZ\Roadiz\Core\Entities\NodeType')
            ->findAll();

        $serializer = new NodeTypeJsonSerializer();
        $zipArchive = new \ZipArchive();
        $tmpfname = tempnam(sys_get_temp_dir(), date('Y-m-d-H-i-s') . '.zip');
        $zipArchive->open($tmpfname, \ZipArchive::CREATE);

        /** @var NodeType $nodeType */
        foreach ($nodeTypes as $nodeType) {
            $zipArchive->addFromString($nodeType->getName() . '.rzt', $serializer->serialize($nodeType));
        }

        $zipArchive->close();
        $response = new BinaryFileResponse($tmpfname);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'nodetypes-' . date('Y-m-d-H-i-s') . '.zip'
        );
        $response->prepare($request);

        return $response;
    }

    /**
     * Import a Json file (.rzt) containing NodeType datas and fields.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function importJsonFileAction(Request $request)
    {
        $this->validateAccessForRole('ROLE_ACCESS_NODETYPES');

        $form = $this->buildImportJsonFileForm();

        $form->handleRequest($request);

        if ($form->isValid() &&
            !empty($form['node_type_file'])) {
            $file = $form['node_type_file']->getData();

            if ($file->isValid()) {
                $serializedData = file_get_contents($file->getPathname());

                if (null !== json_decode($serializedData)) {
                    $serializer = new NodeTypeJsonSerializer();
                    $nodeType = $serializer->deserialize($serializedData);
                    $existingNT = $this->get('em')
                                       ->getRepository('RZ\Roadiz\Core\Entities\NodeType')
                                       ->findOneBy(['name' => $nodeType->getName()]);

                    if (null === $existingNT) {
                        /*
                         * New node-type…
                         *
                         * First persist node-type
                         */
                        $this->get('em')->persist($nodeType);

                        // Flush before creating node-type fields.
                        $this->get('em')->flush();

                        foreach ($nodeType->getFields() as $field) {
                            /*
                             * then persist each field
                             */
                            $field->setNodeType($nodeType);
                            $this->get('em')->persist($field);
                        }

                        $msg = $this->getTranslator()->trans('nodeType.imported.created');
                        $this->publishConfirmMessage($request, $msg);
                    } else {
                        /*
                         * Node-type already exists.
                         * Must update fields.
                         */
                        $existingNT->getHandler()->diff($nodeType);

                        $msg = $this->getTranslator()->trans('nodeType.imported.updated');
                        $this->publishConfirmMessage($request, $msg);
                    }

                    $this->get('em')->flush();
                    $nodeType->getHandler()->updateSchema();

                    /*
                     * Redirect to update schema page
                     */
                    return $this->redirect($this->generateUrl('nodeTypesSchemaUpdate'));
                } else {
                    $msg = $this->getTranslator()->trans('file.format.not_valid');
                    $request->getSession()->getFlashBag()->add('error', $msg);
                    $this->get('logger')->error($msg);

                    // redirect even if its null
                    return $this->redirect($this->generateUrl(
                        'nodeTypesImportPage'
                    ));
                }
            } else {
                $msg = $this->getTranslator()->trans('file.not_uploaded');
                $request->getSession()->getFlashBag()->add('error', $msg);
                $this->get('logger')->error($msg);

                // redirect even if its null
                return $this->redirect($this->generateUrl(
                    'nodeTypesImportPage'
                ));
            }
        }

        $this->assignation['form'] = $form->createView();

        return $this->render('node-types/import.html.twig', $this->assignation);
    }

    /**
     * @return \Symfony\Component\Form\Form
     */
    private function buildImportJsonFileForm()
    {
        $builder = $this->createFormBuilder()
                        ->add('node_type_file', 'file', [
                            'label' => 'nodeType.file',
                        ]);

        return $builder->getForm();
    }
}
