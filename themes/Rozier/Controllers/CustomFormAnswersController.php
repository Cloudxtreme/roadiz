<?php
/**
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
 *
 *
 * @file CustomFormsController.php
 * @author Ambroise Maupate
 */

namespace Themes\Rozier\Controllers;

use RZ\Roadiz\Core\Entities\CustomForm;
use RZ\Roadiz\Core\Entities\CustomFormAnswer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\NotBlank;
use Themes\Rozier\RozierApp;

/**
 * CustomForm controller
 */
class CustomFormAnswersController extends RozierApp
{
    /**
     * List every node-types.
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int                                      $customFormId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function listAction(Request $request, $customFormId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_CUSTOMFORMS');
        /*
         * Manage get request to filter list
         */

        $customForm = $this->getService('em')->find(
            'RZ\Roadiz\Core\Entities\CustomForm',
            $customFormId
        );

        $listManager = $this->createEntityListManager(
            'RZ\Roadiz\Core\Entities\CustomFormAnswer',
            ["customForm" => $customForm],
            ["submittedAt" => "DESC"]
        );
        $listManager->handle();
        $this->assignation['filters'] = $listManager->getAssignation();
        $this->assignation['custom_form_answers'] = $listManager->getEntities();

        return $this->render('custom-form-answers/list.html.twig', $this->assignation);
    }

    /**
     * Return an deletion form for requested node-type.
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int                                      $customFormId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction(Request $request, $customFormAnswerId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_CUSTOMFORMS_DELETE');

        $customFormAnswer = $this->getService('em')
                                 ->find('RZ\Roadiz\Core\Entities\CustomFormAnswer', (int) $customFormAnswerId);

        if (null !== $customFormAnswer) {
            $this->assignation['customFormAnswer'] = $customFormAnswer;

            $form = $this->buildDeleteForm($customFormAnswer);

            $form->handleRequest($request);

            if ($form->isValid() &&
                $form->getData()['customFormAnswerId'] == $customFormAnswer->getId()) {
                $this->getService("em")->remove($customFormAnswer);

                $msg = $this->getTranslator()->trans('customFormAnswer.%id%.deleted', ['%id%' => $customFormAnswer->getId()]);
                $this->publishConfirmMessage($request, $msg);
                /*
                 * Redirect to update schema page
                 */
                return $this->redirect($this->generateUrl(
                    'customFormAnswersHomePage',
                    ["customFormId" => $customFormAnswer->getCustomForm()->getId()]
                ));
            }

            $this->assignation['form'] = $form->createView();

            return $this->render('custom-form-answers/delete.html.twig', $this->assignation);
        } else {
            return $this->throw404();
        }
    }

    /**
     * @param RZ\Roadiz\Core\Entities\CustomForm $customForm
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildDeleteForm(CustomFormAnswer $customFormAnswer)
    {
        $builder = $this->createFormBuilder()
                        ->add('customFormAnswerId', 'hidden', [
                            'data' => $customFormAnswer->getId(),
                            'constraints' => [
                                new NotBlank(),
                            ],
                        ]);

        return $builder->getForm();
    }
}
