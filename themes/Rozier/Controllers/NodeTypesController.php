<?php
/**
 * Copyright REZO ZERO 2014
 *
 *
 *
 *
 * @file NodeTypesController.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */

namespace Themes\Rozier\Controllers;

use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\ListManagers\EntityListManager;
use Themes\Rozier\RozierApp;

use RZ\Roadiz\Core\Exceptions\EntityAlreadyExistsException;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Validator\Constraints\NotBlank;

use RZ\Roadiz\Core\Kernel;

/**
* NodeType controller
*/
class NodeTypesController extends RozierApp
{
    /**
     * List every node-types.
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        $this->validateAccessForRole('ROLE_ACCESS_NODETYPES');
        /*
         * Manage get request to filter list
         */
        $listManager = new EntityListManager(
            $request,
            $this->getService('em'),
            'RZ\Roadiz\Core\Entities\NodeType'
        );
        $listManager->handle();

        $this->assignation['filters'] = $listManager->getAssignation();
        $this->assignation['node_types'] = $listManager->getEntities();

        return new Response(
            $this->getTwig()->render('node-types/list.html.twig', $this->assignation),
            Response::HTTP_OK,
            array('content-type' => 'text/html')
        );
    }

    /**
     * Return an edition form for requested node-type.
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int                                      $nodeTypeId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Request $request, $nodeTypeId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_NODETYPES');

        $nodeType = $this->getService('em')
            ->find('RZ\Roadiz\Core\Entities\NodeType', (int) $nodeTypeId);

        if (null !== $nodeType) {
            $this->assignation['nodeType'] = $nodeType;

            $form = $this->buildEditForm($nodeType);

            $form->handleRequest();

            if ($form->isValid()) {
                try {
                    $this->editNodeType($form->getData(), $nodeType);

                    $msg = $this->getTranslator()->trans('nodeType.%name%.updated', array('%name%'=>$nodeType->getName()));
                    $request->getSession()->getFlashBag()->add('confirm', $msg);
                    $this->getService('logger')->info($msg);
                } catch (EntityAlreadyExistsException $e) {
                    $request->getSession()->getFlashBag()->add('error', $e->getMessage());
                    $this->getService('logger')->warning($e->getMessage());
                }
                /*
                 * Redirect to update schema page
                 */
                $response = new RedirectResponse(
                    $this->getService('urlGenerator')->generate(
                        'nodeTypesSchemaUpdate',
                        array(
                            '_token' => $this->getService('csrfProvider')->generateCsrfToken(static::SCHEMA_TOKEN_INTENTION)
                        )
                    )
                );
                $response->prepare($request);

                return $response->send();
            }

            $this->assignation['form'] = $form->createView();

            return new Response(
                $this->getTwig()->render('node-types/edit.html.twig', $this->assignation),
                Response::HTTP_OK,
                array('content-type' => 'text/html')
            );
        } else {
            return $this->throw404();
        }
    }

    /**
     * Return an creation form for requested node-type.
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function addAction(Request $request)
    {
        $this->validateAccessForRole('ROLE_ACCESS_NODETYPES');

        $nodeType = new NodeType();

        if (null !== $nodeType) {
            $this->assignation['nodeType'] = $nodeType;

            /*
             * form
             */
            $form = $this->buildAddForm($nodeType);
            $form->handleRequest();
            if ($form->isValid()) {
                try {
                    $this->addNodeType($form->getData(), $nodeType);

                    $msg = $this->getTranslator()->trans('nodeType.%name%.created', array('%name%'=>$nodeType->getName()));
                    $request->getSession()->getFlashBag()->add('confirm', $msg);
                    $this->getService('logger')->info($msg);

                    /*
                     * Redirect to update schema page
                     */
                    $response = new RedirectResponse(
                        $this->getService('urlGenerator')->generate(
                            'nodeTypesSchemaUpdate',
                            array(
                                '_token' => $this->getService('csrfProvider')->generateCsrfToken(static::SCHEMA_TOKEN_INTENTION)
                            )
                        )
                    );

                } catch (EntityAlreadyExistsException $e) {
                    $request->getSession()->getFlashBag()->add('error', $e->getMessage());
                    $this->getService('logger')->warning($e->getMessage());
                    $response = new RedirectResponse(
                        $this->getService('urlGenerator')->generate(
                            'nodeTypesAddPage'
                        )
                    );
                }
                $response->prepare($request);

                return $response->send();
            }

            $this->assignation['form'] = $form->createView();

            return new Response(
                $this->getTwig()->render('node-types/add.html.twig', $this->assignation),
                Response::HTTP_OK,
                array('content-type' => 'text/html')
            );
        } else {
            return $this->throw404();
        }
    }

    /**
     * Return an deletion form for requested node-type.
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int                                      $nodeTypeId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction(Request $request, $nodeTypeId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_NODETYPES_DELETE');

        $nodeType = $this->getService('em')
            ->find('RZ\Roadiz\Core\Entities\NodeType', (int) $nodeTypeId);

        if (null !== $nodeType) {
            $this->assignation['nodeType'] = $nodeType;

            $form = $this->buildDeleteForm($nodeType);

            $form->handleRequest();

            if ($form->isValid() &&
                $form->getData()['nodeTypeId'] == $nodeType->getId() ) {

                /*
                 * Delete All node-type association and schema
                 */
                $nodeType->getHandler()->deleteWithAssociations();

                $msg = $this->getTranslator()->trans('nodeType.%name%.deleted', array('%name%'=>$nodeType->getName()));
                $request->getSession()->getFlashBag()->add('confirm', $msg);
                $this->getService('logger')->info($msg);
                /*
                 * Redirect to update schema page
                 */
                $response = new RedirectResponse(
                    $this->getService('urlGenerator')->generate(
                        'nodeTypesSchemaUpdate',
                        array(
                            '_token' => $this->getService('csrfProvider')->generateCsrfToken(static::SCHEMA_TOKEN_INTENTION)
                        )
                    )
                );
                $response->prepare($request);

                return $response->send();
            }

            $this->assignation['form'] = $form->createView();

            return new Response(
                $this->getTwig()->render('node-types/delete.html.twig', $this->assignation),
                Response::HTTP_OK,
                array('content-type' => 'text/html')
            );
        } else {
            return $this->throw404();
        }
    }

    /**
     * @param array                           $data
     * @param RZ\Roadiz\Core\Entities\NodeType $nodeType
     *
     * @return boolean
     */
    private function editNodeType($data, NodeType $nodeType)
    {
        foreach ($data as $key => $value) {
            if (isset($data['name'])) {
                throw new EntityAlreadyExistsException($this->getTranslator()->trans('nodeType.%name%.cannot_rename_already_exists', array('%name%'=>$nodeType->getName())), 1);
            }
            $setter = 'set'.ucwords($key);
            $nodeType->$setter( $value );
        }

        $this->getService('em')->flush();
        $nodeType->getHandler()->updateSchema();

        return true;
    }

    /**
     * @param array                           $data
     * @param RZ\Roadiz\Core\Entities\NodeType $nodeType
     *
     * @return boolean
     */
    private function addNodeType($data, NodeType $nodeType)
    {
        foreach ($data as $key => $value) {
            $setter = 'set'.ucwords($key);
            $nodeType->$setter( $value );
        }

        $existing = $this->getService('em')
            ->getRepository('RZ\Roadiz\Core\Entities\NodeType')
            ->findOneBy(array('name'=>$nodeType->getName()));
        if ($existing !== null) {
            throw new EntityAlreadyExistsException($this->getTranslator()->trans('nodeType.%name%.already_exists', array('%name%'=>$nodeType->getName())), 1);
        }

        $this->getService('em')->persist($nodeType);
        $this->getService('em')->flush();

        $nodeType->getHandler()->updateSchema();

        return true;
    }

    /**
     * @param RZ\Roadiz\Core\Entities\NodeType $nodeType
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildAddForm(NodeType $nodeType)
    {
        $defaults = array(
            'name' =>           $nodeType->getName(),
            'displayName' =>    $nodeType->getDisplayName(),
            'description' =>    $nodeType->getDescription(),
            'visible' =>        $nodeType->isVisible(),
            'newsletterType' => $nodeType->isNewsletterType(),
            'hidingNodes' =>    $nodeType->isHidingNodes(),
            'color' =>          $nodeType->getColor(),
        );
        $builder = $this->getService('formFactory')
            ->createBuilder('form', $defaults)
            ->add('name', 'text', array(
                'label' => $this->getTranslator()->trans('name'),
                'constraints' => array(
                    new NotBlank()
                )));

        $this->buildCommonFormFields($builder);

        return $builder->getForm();
    }

    /**
     * @param RZ\Roadiz\Core\Entities\NodeType $nodeType
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildEditForm(NodeType $nodeType)
    {
        $defaults = array(
            'displayName' =>    $nodeType->getDisplayName(),
            'description' =>    $nodeType->getDescription(),
            'visible' =>        $nodeType->isVisible(),
            'newsletterType' => $nodeType->isNewsletterType(),
            'hidingNodes' =>    $nodeType->isHidingNodes(),
            'color' =>          $nodeType->getColor(),
        );
        $builder = $this->getService('formFactory')
            ->createBuilder('form', $defaults);

        $this->buildCommonFormFields($builder);

        return $builder->getForm();
    }

    /**
     * Build common fields between add and edit node-type forms.
     *
     * @param FormBuilder $builder
     */
    private function buildCommonFormFields(&$builder)
    {
        $builder->add('displayName', 'text', array(
            'label' => $this->getTranslator()->trans('nodeType.displayName'),
            'constraints' => array(
                new NotBlank()
            )))
        ->add('description', 'text', array(
            'label' => $this->getTranslator()->trans('description'),
            'required' => false
        ))
        ->add('visible', 'checkbox', array(
            'label' => $this->getTranslator()->trans('visible'),
            'required' => false
        ))
        ->add('newsletterType', 'checkbox', array(
            'label' => $this->getTranslator()->trans('nodeType.newsletterType'),
            'required' => false
        ))
        ->add('hidingNodes', 'checkbox', array(
            'label' => $this->getTranslator()->trans('nodeType.hidingNodes'),
            'required' => false
        ))
        ->add('color', 'text', array(
            'label' => $this->getTranslator()->trans('nodeType.color'),
            'required' => false,
            'attr' => array('class'=>'colorpicker-input')
        ));

        return $builder;
    }

    /**
     * @param RZ\Roadiz\Core\Entities\NodeType $nodeType
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildDeleteForm(NodeType $nodeType)
    {
        $builder = $this->getService('formFactory')
            ->createBuilder('form')
            ->add('nodeTypeId', 'hidden', array(
                'data' => $nodeType->getId(),
                'constraints' => array(
                    new NotBlank()
                )
            ));

        return $builder->getForm();
    }

    /**
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public static function getNewsletterNodeTypes()
    {
        return Kernel::getService('em')
            ->getRepository('RZ\Roadiz\Core\Entities\NodeType')
            ->findBy(array('newsletterType' => true));
    }
}
