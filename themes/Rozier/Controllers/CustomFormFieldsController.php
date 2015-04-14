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
 * @file CustomFormFieldsController.php
 * @author Ambroise Maupate
 */
namespace Themes\Rozier\Controllers;

use \RZ\Roadiz\CMS\Forms\MarkdownType;
use \RZ\Roadiz\Core\Entities\CustomForm;
use \RZ\Roadiz\Core\Entities\CustomFormField;
use \RZ\Roadiz\Core\Exceptions\EntityAlreadyExistsException;
use \Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\Validator\Constraints\NotBlank;
use \Themes\Rozier\RozierApp;

/**
 * {@inheritdoc}
 */
class CustomFormFieldsController extends RozierApp
{
    /**
     * List every node-type-fields.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int $customFormId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function listAction(Request $request, $customFormId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_CUSTOMFORMS');

        $customForm = $this->getService('em')
                           ->find('RZ\Roadiz\Core\Entities\CustomForm', (int) $customFormId);

        if ($customForm !== null) {
            $fields = $customForm->getFields();

            $this->assignation['customForm'] = $customForm;
            $this->assignation['fields'] = $fields;

            return $this->render('custom-form-fields/list.html.twig', $this->assignation);
        } else {
            return $this->throw404();
        }
    }

    /**
     * Return an edition form for requested node-type.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int                                      $customFormFieldId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Request $request, $customFormFieldId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_CUSTOMFORMS');

        $field = $this->getService('em')
                      ->find('RZ\Roadiz\Core\Entities\CustomFormField', (int) $customFormFieldId);

        if ($field !== null) {
            $this->assignation['customForm'] = $field->getCustomForm();
            $this->assignation['field'] = $field;
            $form = $this->buildEditForm($field);
            $form->handleRequest();

            if ($form->isValid()) {
                $this->editCustomFormField($form->getData(), $field);

                $msg = $this->getTranslator()->trans('customFormField.%name%.updated', ['%name%' => $field->getName()]);
                $this->publishConfirmMessage($request, $msg);

                /*
                 * Redirect to update schema page
                 */
                return $this->redirect($this->generateUrl(
                    'customFormFieldsListPage',
                    [
                        'customFormId' => $field->getCustomForm()->getId(),
                    ]
                ));
            }

            $this->assignation['form'] = $form->createView();

            return $this->render('custom-form-fields/edit.html.twig', $this->assignation);
        } else {
            return $this->throw404();
        }
    }

    /**
     * Return an creation form for requested node-type.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int                                      $customFormId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function addAction(Request $request, $customFormId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_CUSTOMFORMS');

        $field = new CustomFormField();
        $customForm = $this->getService('em')
                           ->find('RZ\Roadiz\Core\Entities\CustomForm', (int) $customFormId);

        if ($customForm !== null &&
            $field !== null) {
            $this->assignation['customForm'] = $customForm;
            $this->assignation['field'] = $field;
            $form = $this->buildEditForm($field);
            $form->handleRequest();

            if ($form->isValid()) {
                try {
                    $this->addCustomFormField($form->getData(), $field, $customForm);

                    $msg = $this->getTranslator()->trans(
                        'customFormField.%name%.created',
                        ['%name%' => $field->getName()]
                    );
                    $this->publishConfirmMessage($request, $msg);

                    /*
                     * Redirect to update schema page
                     */
                    return $this->redirect($this->generateUrl(
                        'customFormFieldsListPage',
                        [
                            'customFormId' => $customFormId,
                        ]
                    ));

                } catch (\Exception $e) {
                    $msg = $e->getMessage();
                    $request->getSession()->getFlashBag()->add('error', $msg);
                    $this->getService('logger')->error($msg);
                    /*
                     * Redirect to add page
                     */
                    return $this->redirect($this->generateUrl(
                        'customFormFieldsAddPage',
                        ['customFormId' => $customFormId]
                    ));
                }
            }

            $this->assignation['form'] = $form->createView();

            return $this->render('custom-form-fields/add.html.twig', $this->assignation);
        } else {
            return $this->throw404();
        }
    }

    /**
     * Return an deletion form for requested node.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int                                      $customFormFieldId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction(Request $request, $customFormFieldId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_CUSTOMFORMS_DELETE');

        $field = $this->getService('em')
                      ->find('RZ\Roadiz\Core\Entities\CustomFormField', (int) $customFormFieldId);

        if ($field !== null) {
            $this->assignation['field'] = $field;
            $form = $this->buildDeleteForm($field);
            $form->handleRequest();

            if ($form->isValid() &&
                $form->getData()['customFormFieldId'] == $field->getId()) {
                $customFormId = $field->getCustomForm()->getId();

                $this->getService('em')->remove($field);
                $this->getService('em')->flush();

                /*
                 * Update Database
                 */
                $msg = $this->getTranslator()->trans(
                    'customFormField.%name%.deleted',
                    ['%name%' => $field->getName()]
                );
                $this->publishConfirmMessage($request, $msg);

                /*
                 * Redirect to update schema page
                 */
                return $this->redirect($this->generateUrl(
                    'customFormFieldsListPage',
                    [
                        'customFormId' => $customFormId,
                    ]
                ));
            }

            $this->assignation['form'] = $form->createView();

            return $this->render('custom-form-fields/delete.html.twig', $this->assignation);
        } else {
            return $this->throw404();
        }
    }

    /**
     * @param array                                $data
     * @param RZ\Roadiz\Core\Entities\CustomFormField $field
     */
    private function editCustomFormField($data, CustomFormField $field)
    {
        foreach ($data as $key => $value) {
            $setter = 'set' . ucwords($key);
            $field->$setter($value);
        }

        $this->getService('em')->flush();
    }

    /**
     * @param array                                  $data
     * @param RZ\Roadiz\Core\Entities\CustomFormField $field
     * @param RZ\Roadiz\Core\Entities\CustomForm      $customForm
     */
    private function addCustomFormField(
        $data,
        CustomFormField $field,
        CustomForm $customForm
    ) {
        /*
         * Check existing
         */
        $existing = $this->getService('em')
                         ->getRepository('RZ\Roadiz\Core\Entities\CustomFormField')
                         ->findOneBy([
                             'name' => $data['name'],
                             'customForm' => $customForm,
                         ]);
        if (null !== $existing) {
            throw new EntityAlreadyExistsException($this->getTranslator()->trans(
                "%field%.already_exists",
                ['%field%' => $data['name']]
            ), 1);
        }

        foreach ($data as $key => $value) {
            $setter = 'set' . ucwords($key);
            $field->$setter($value);
        }

        $field->setCustomForm($customForm);
        $this->getService('em')->persist($field);

        $customForm->addField($field);
        $this->getService('em')->flush();
    }

    /**
     * @param RZ\Roadiz\Core\Entities\CustomFormField $field
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildEditForm(CustomFormField $field)
    {
        $defaults = [
            'name' => $field->getName(),
            'label' => $field->getLabel(),
            'type' => $field->getType(),
            'description' => $field->getDescription(),
            'required' => $field->isRequired(),
            'defaultValues' => $field->getDefaultValues(),
        ];
        $builder = $this->getService('formFactory')
                        ->createBuilder('form', $defaults)
                        ->add('name', 'text', [
                            'label' => $this->getTranslator()->trans('name'),
                            'constraints' => [
                                new NotBlank(),
                                new \RZ\Roadiz\CMS\Forms\Constraints\NonSqlReservedWord(),
                                new \RZ\Roadiz\CMS\Forms\Constraints\SimpleLatinString(),
                            ],
                        ])
                        ->add('label', 'text', [
                            'label' => $this->getTranslator()->trans('label'),
                            'constraints' => [
                                new NotBlank(),
                            ],
                        ])
                        ->add('description', new MarkdownType(), [
                            'label' => $this->getTranslator()->trans('description'),
                            'required' => false,
                        ])
                        ->add('type', 'choice', [
                            'label' => $this->getTranslator()->trans('type'),
                            'required' => true,
                            'choices' => CustomFormField::$typeToHuman,
                        ])
                        ->add('required', 'checkbox', [
                            'label' => $this->getTranslator()->trans('required'),
                            'required' => false,
                        ])
                        ->add(
                            'defaultValues',
                            'text',
                            [
                                'label' => $this->getTranslator()->trans('defaultValues'),
                                'required' => false,
                                'attr' => [
                                    'placeholder' => $this->getTranslator()->trans('enter_values_comma_separated'),
                                ],
                            ]
                        );

        return $builder->getForm();
    }

    /**
     * @param RZ\Roadiz\Core\Entities\CustomFormField $field
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildDeleteForm(CustomFormField $field)
    {
        $builder = $this->getService('formFactory')
                        ->createBuilder('form')
                        ->add('customFormFieldId', 'hidden', [
                            'data' => $field->getId(),
                            'constraints' => [
                                new NotBlank(),
                            ],
                        ]);

        return $builder->getForm();
    }
}
