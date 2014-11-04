<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file CustomFormFieldsController.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace Themes\Rozier\Controllers;

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\CustomForm;
use RZ\Renzo\Core\Entities\CustomFormField;
use RZ\Renzo\Core\ListManagers\EntityListManager;
use RZ\Renzo\Core\Exceptions\EntityAlreadyExistsException;
use RZ\Renzo\Core\Exceptions\ReservedSQLWordException;
use Themes\Rozier\RozierApp;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use \Symfony\Component\Form\Form;
use \Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use \Symfony\Component\Validator\Constraints\NotBlank;
use \Symfony\Component\Validator\Constraints\Type;

/**
 * {@inheritdoc}
 */
class CustomFormFieldsController extends RozierApp
{
    /**
     * List every node-type-fields.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int                                      $customFormId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function listAction(Request $request, $customFormId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_CUSTOMFORMS');

        $customForm = $this->getService('em')
            ->find('RZ\Renzo\Core\Entities\CustomForm', (int) $customFormId);

        if ($customForm !== null) {
            $fields = $customForm->getFields();

            $this->assignation['customForm'] = $customForm;
            $this->assignation['fields'] = $fields;

            return new Response(
                $this->getTwig()->render('custom-form-fields/list.html.twig', $this->assignation),
                Response::HTTP_OK,
                array('content-type' => 'text/html')
            );
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
            ->find('RZ\Renzo\Core\Entities\CustomFormField', (int) $customFormFieldId);

        if ($field !== null) {

            $this->assignation['customForm'] = $field->getCustomForm();
            $this->assignation['field'] = $field;
            $form = $this->buildEditForm($field);
            $form->handleRequest();

            if ($form->isValid()) {
                $this->editCustomFormField($form->getData(), $field);

                $msg = $this->getTranslator()->trans('customFormField.%name%.updated', array('%name%'=>$field->getName()));
                $request->getSession()->getFlashBag()->add('confirm', $msg);
                $this->getService('logger')->info($msg);

                /*
                 * Redirect to update schema page
                 */
                $response = new RedirectResponse(
                    $this->getService('urlGenerator')->generate(
                        'customFormFieldsListPage',
                        array(
                            'customFormId' => $field->getCustomForm()->getId()
                        )
                    )
                );
                $response->prepare($request);

                return $response->send();
            }

            $this->assignation['form'] = $form->createView();

            return new Response(
                $this->getTwig()->render('custom-form-fields/edit.html.twig', $this->assignation),
                Response::HTTP_OK,
                array('content-type' => 'text/html')
            );
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
            ->find('RZ\Renzo\Core\Entities\CustomForm', (int) $customFormId);

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
                        array('%name%'=>$field->getName())
                    );
                    $request->getSession()->getFlashBag()->add('confirm', $msg);
                    $this->getService('logger')->info($msg);


                    /*
                     * Redirect to update schema page
                     */
                    $response = new RedirectResponse(
                        $this->getService('urlGenerator')->generate(
                            'customFormFieldsListPage',
                            array(
                                'customFormId' => $customFormId
                            )
                        )
                    );

                } catch (\Exception $e) {
                    $msg = $e->getMessage();
                    $request->getSession()->getFlashBag()->add('error', $msg);
                    $this->getService('logger')->error($msg);
                    /*
                     * Redirect to add page
                     */
                    $response = new RedirectResponse(
                        $this->getService('urlGenerator')->generate(
                            'customFormFieldsAddPage',
                            array('customFormId' => $customFormId)
                        )
                    );
                }

                $response->prepare($request);
                return $response->send();
            }

            $this->assignation['form'] = $form->createView();

            return new Response(
                $this->getTwig()->render('custom-form-fields/add.html.twig', $this->assignation),
                Response::HTTP_OK,
                array('content-type' => 'text/html')
            );
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
            ->find('RZ\Renzo\Core\Entities\CustomFormField', (int) $customFormFieldId);

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
                $customForm = $this->getService('em')
                    ->find('RZ\Renzo\Core\Entities\CustomForm', (int) $customFormId);

                $msg = $this->getTranslator()->trans(
                    'customFormField.%name%.deleted',
                    array('%name%'=>$field->getName())
                );
                $request->getSession()->getFlashBag()->add('confirm', $msg);
                $this->getService('logger')->info($msg);

                /*
                 * Redirect to update schema page
                 */
                $response = new RedirectResponse(
                    $this->getService('urlGenerator')->generate(
                        'customFormFieldsListPage',
                        array(
                            'customFormId' => $customFormId
                        )
                    )
                );
                $response->prepare($request);

                return $response->send();
            }

            $this->assignation['form'] = $form->createView();

            return new Response(
                $this->getTwig()->render('custom-form-fields/delete.html.twig', $this->assignation),
                Response::HTTP_OK,
                array('content-type' => 'text/html')
            );
        } else {
            return $this->throw404();
        }
    }

    /**
     * @param array                                $data
     * @param RZ\Renzo\Core\Entities\CustomFormField $field
     */
    private function editCustomFormField($data, CustomFormField $field)
    {
        foreach ($data as $key => $value) {
            $setter = 'set'.ucwords($key);
            $field->$setter($value);
        }

        $this->getService('em')->flush();
    }

    /**
     * @param array                                $data
     * @param RZ\Renzo\Core\Entities\CustomFormField $field
     * @param RZ\Renzo\Core\Entities\CustomForm      $customForm
     */
    private function addCustomFormField(
        $data,
        CustomFormField $field,
        CustomForm $customForm
    ) {

        /*
         * Check reserved words
         */
        if (in_array(strtolower($data['name']), CustomFormField::$forbiddenNames)) {
            throw new ReservedSQLWordException($this->getTranslator()->trans(
                "%field%.is.reserved.word",
                array('%field%' => $data['name'])
            ), 1);
        }

        /*
         * Check existing
         */
        $existing = $this->getService('em')
                         ->getRepository('RZ\Renzo\Core\Entities\CustomFormField')
                         ->findOneBy(array(
                            'name' => $data['name'],
                            'customForm' => $customForm
                        ));
        if (null !== $existing) {
            throw new EntityAlreadyExistsException($this->getTranslator()->trans(
                "%field%.already_exists",
                array('%field%' => $data['name'])
            ), 1);
        }

        foreach ($data as $key => $value) {
            $setter = 'set'.ucwords($key);
            $field->$setter($value);
        }

        $field->setCustomForm($customForm);
        $this->getService('em')->persist($field);

        $customForm->addField($field);
        $this->getService('em')->flush();
    }

    /**
     * @param RZ\Renzo\Core\Entities\CustomFormField $field
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildEditForm(CustomFormField $field)
    {
        $defaults = array(
            'name' =>           $field->getName(),
            'label' =>          $field->getLabel(),
            'type' =>           $field->getType(),
            'description' =>    $field->getDescription(),
            'require' =>        $field->isRequire(),
            'defaultValues' =>  $field->getDefaultValues(),
        );
        $builder = $this->getService('formFactory')
                    ->createBuilder('form', $defaults)
                    ->add('name', 'text', array(
                        'label' => $this->getTranslator()->trans('name'),
                        'constraints' => array(
                            new NotBlank()
                        )
                    ))
                    ->add('label', 'text', array(
                        'label' => $this->getTranslator()->trans('label'),
                        'constraints' => array(
                            new NotBlank()
                        )
                    ))
                    ->add('type', 'choice', array(
                        'label' => $this->getTranslator()->trans('type'),
                        'required' => true,
                        'choices' => CustomFormField::$typeToHuman
                    ))
                    ->add('description', 'text', array(
                        'label' => $this->getTranslator()->trans('description'),
                        'required' => false
                    ))
                    ->add('require', 'checkbox', array(
                        'label' => $this->getTranslator()->trans('require'),
                        'required' => false
                    ))
                    ->add(
                        'defaultValues',
                        'text',
                        array(
                            'label' => $this->getTranslator()->trans('defaultValues'),
                            'required' => false,
                            'attr' => array(
                                'placeholder' => $this->getTranslator()->trans('enter_values_comma_separated')
                            )
                        )
                    );

        return $builder->getForm();
    }

    /**
     * @param RZ\Renzo\Core\Entities\CustomFormField $field
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildDeleteForm(CustomFormField $field)
    {
        $builder = $this->getService('formFactory')
            ->createBuilder('form')
            ->add('customFormFieldId', 'hidden', array(
                'data' => $field->getId(),
                'constraints' => array(
                    new NotBlank()
                )
            ));

        return $builder->getForm();
    }
}
