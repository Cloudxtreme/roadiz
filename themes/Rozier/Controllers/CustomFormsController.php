<?php
/**
 * Copyright (c) 2016, Ambroise Maupate and Julien Blanchet
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
 * CustomFormsController.php
 * @author ambroisemaupate
 */
namespace Themes\Rozier\Controllers;

use RZ\Roadiz\Core\Entities\CustomForm;
use RZ\Roadiz\Core\Exceptions\EntityAlreadyExistsException;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Validator\Constraints\NotBlank;
use Themes\Rozier\Forms\CustomFormType;
use Themes\Rozier\RozierApp;

/**
 * CustomForm controller
 */
class CustomFormsController extends RozierApp
{
    /**
     * List every node-types.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        $this->validateAccessForRole('ROLE_ACCESS_CUSTOMFORMS');
        /*
         * Manage get request to filter list
         */
        $listManager = $this->createEntityListManager(
            'RZ\Roadiz\Core\Entities\CustomForm'
        );
        $listManager->setDisplayingNotPublishedNodes(true);
        $listManager->handle();

        $this->assignation['filters'] = $listManager->getAssignation();
        $this->assignation['custom_forms'] = $listManager->getEntities();

        return $this->render('custom-forms/list.html.twig', $this->assignation);
    }

    /**
     * Return an edition form for requested node-type.
     *
     * @param Request   $request
     * @param int $customFormId
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Request $request, $customFormId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_CUSTOMFORMS');

        $customForm = $this->get('em')
                           ->find('RZ\Roadiz\Core\Entities\CustomForm', (int) $customFormId);

        if (null !== $customForm) {
            $this->assignation['customForm'] = $customForm;

            $form = $this->createForm(CustomFormType::class, $customForm, [
                'em' => $this->get('em'),
                'name' => $customForm->getName(),
            ]);
            $form->handleRequest($request);

            if ($form->isValid()) {
                try {
                    $this->get('em')->flush();

                    $msg = $this->getTranslator()->trans('customForm.%name%.updated', ['%name%' => $customForm->getName()]);
                    $this->publishConfirmMessage($request, $msg);
                } catch (EntityAlreadyExistsException $e) {
                    $this->publishErrorMessage($request, $e->getMessage());
                }
                /*
                 * Redirect to update schema page
                 */
                return $this->redirect($this->generateUrl(
                    'customFormsHomePage',
                    [
                        '_token' => $this->get('csrfTokenManager')->getToken(static::SCHEMA_TOKEN_INTENTION),
                    ]
                ));
            }

            $this->assignation['form'] = $form->createView();

            return $this->render('custom-forms/edit.html.twig', $this->assignation);
        }

        throw new ResourceNotFoundException();
    }

    /**
     * Return an creation form for requested node-type.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function addAction(Request $request)
    {
        $this->validateAccessForRole('ROLE_ACCESS_CUSTOMFORMS');

        $customForm = new CustomForm();

        if (null !== $customForm) {
            $this->assignation['customForm'] = $customForm;

            /*
             * form
             */
            $form = $this->createForm(CustomFormType::class, $customForm, [
                'em' => $this->get('em'),
            ]);
            $form->handleRequest($request);
            if ($form->isValid()) {
                try {
                    $this->get('em')->persist($customForm);
                    $this->get('em')->flush();

                    $msg = $this->getTranslator()->trans('customForm.%name%.created', ['%name%' => $customForm->getName()]);
                    $this->publishConfirmMessage($request, $msg);

                    /*
                     * Redirect to update schema page
                     */
                    return $this->redirect($this->generateUrl(
                        'customFormsHomePage'
                    ));
                } catch (EntityAlreadyExistsException $e) {
                    $this->publishErrorMessage($request, $e->getMessage());
                    return $this->redirect($this->generateUrl(
                        'customFormsAddPage'
                    ));
                }
            }

            $this->assignation['form'] = $form->createView();

            return $this->render('custom-forms/add.html.twig', $this->assignation);
        }

        throw new ResourceNotFoundException();
    }

    /**
     * Return an deletion form for requested node-type.
     *
     * @param Request $request
     * @param int     $customFormId
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction(Request $request, $customFormId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_CUSTOMFORMS_DELETE');

        $customForm = $this->get('em')
                           ->find('RZ\Roadiz\Core\Entities\CustomForm', (int) $customFormId);

        if (null !== $customForm) {
            $this->assignation['customForm'] = $customForm;

            $form = $this->buildDeleteForm($customForm);

            $form->handleRequest($request);

            if ($form->isValid() &&
                $form->getData()['customFormId'] == $customForm->getId()) {
                $this->get("em")->remove($customForm);

                $msg = $this->getTranslator()->trans('customForm.%name%.deleted', ['%name%' => $customForm->getName()]);
                $this->publishConfirmMessage($request, $msg);
                /*
                 * Redirect to update schema page
                 */
                return $this->redirect($this->generateUrl(
                    'customFormsHomePage'
                ));
            }

            $this->assignation['form'] = $form->createView();

            return $this->render('custom-forms/delete.html.twig', $this->assignation);
        }

        throw new ResourceNotFoundException();
    }

    /**
     * @param CustomForm $customForm
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildDeleteForm(CustomForm $customForm)
    {
        $builder = $this->createFormBuilder()
                        ->add('customFormId', HiddenType::class, [
                            'data' => $customForm->getId(),
                            'constraints' => [
                                new NotBlank(),
                            ],
                        ]);

        return $builder->getForm();
    }
}
