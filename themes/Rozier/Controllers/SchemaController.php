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
 * Description
 *
 * @file SchemaController.php
 * @author Ambroise Maupate
 */

namespace Themes\Rozier\Controllers;

use RZ\Roadiz\Utils\Doctrine\SchemaUpdater;
use Symfony\Component\HttpFoundation\Request;
use Themes\Rozier\RozierApp;

/**
 * Redirection controller use to update database schema.
 */
class SchemaController extends RozierApp
{
    /**
     * No preparation for this blind controller.
     *
     * @return $this
     */
    public function prepareBaseAssignation()
    {
        return $this;
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function updateNodeTypesSchemaAction(Request $request)
    {
        $this->validateAccessForRole('ROLE_ACCESS_NODETYPES');
        $this->updateSchema($request);

        return $this->redirect($this->generateUrl(
            'nodeTypesHomePage'
        ));
    }

    /**
     * @param Request $request
     * @param int     $nodeTypeId
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function updateNodeTypeFieldsSchemaAction(Request $request, $nodeTypeId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_NODETYPES');
        $this->updateSchema($request);

        return $this->redirect($this->generateUrl(
            'nodeTypeFieldsListPage',
            [
                'nodeTypeId' => $nodeTypeId,
            ]
        ));
    }

    /**
     * @param Request $request
     */
    protected function updateSchema(Request $request)
    {
        $updater = new SchemaUpdater($this->get('em'), $this->get('kernel'));
        $updater->updateSchema();

        $msg = $this->getTranslator()->trans('database.schema.updated');
        $this->publishConfirmMessage($request, $msg);
    }
}
