<?php 
/**
 * Copyright REZO ZERO 2014
 * 
 * 
 * 
 *
 * @file NodesController.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace Themes\Rozier\Controllers;

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\Node;
use RZ\Renzo\Core\Entities\NodeType;
use RZ\Renzo\Core\Entities\NodeTypeField;
use RZ\Renzo\Core\Entities\UrlAlias;
use RZ\Renzo\Core\Entities\Translation;
use RZ\Renzo\Core\Handlers\NodeHandler;
use RZ\Renzo\Core\Utils\StringHandler;
use Themes\Rozier\RozierApp;

use RZ\Renzo\Core\Exceptions\EntityAlreadyExistsException;
use RZ\Renzo\Core\Exceptions\NoTranslationAvailableException;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;



class NodesController extends RozierApp {
	
	/**
	 * List every nodes
	 * @return Symfony\Component\HttpFoundation\Response
	 */
	public function indexAction( Request $request )
	{
		/*
		 * Apply ordering or not
		 */
		try {
			if ($request->query->get('field') && 
				$request->query->get('ordering')) {
				$nodes = Kernel::getInstance()->em()
					->getRepository('RZ\Renzo\Core\Entities\Node')
					->findBy(array(), array($request->query->get('field') => $request->query->get('ordering')));
			}
			else {
				$nodes = Kernel::getInstance()->em()
					->getRepository('RZ\Renzo\Core\Entities\Node')
					->findAll();
			}
		}
		catch(\Doctrine\ORM\ORMException $e){
			return $this->throw404();
		}

		$translation = Kernel::getInstance()->em()
				->getRepository('RZ\Renzo\Core\Entities\Translation')
				->findOneBy(array('defaultTranslation'=>true));

		$this->assignation['nodes'] = $nodes;
		$this->assignation['node_types'] = NodeTypesController::getNodeTypes();
		$this->assignation['translation'] = $translation;

		return new Response(
			$this->getTwig()->render('nodes/list.html.twig', $this->assignation),
			Response::HTTP_OK,
			array('content-type' => 'text/html')
		);
	}

	/**
	 * Return an edition form for requested node
	 * 
	 * @param  integer $node_id        [description]
	 * @param  integer $translation_id [description]
	 * @return Symfony\Component\HttpFoundation\Response
	 */
	public function editAction( Request $request, $node_id, $translation_id = null )
	{
		$translation = Kernel::getInstance()->em()
				->getRepository('RZ\Renzo\Core\Entities\Translation')
				->findOneBy(array('defaultTranslation'=>true));
		$node = Kernel::getInstance()->em()
			->find('RZ\Renzo\Core\Entities\Node', (int)$node_id);

		if ($node !== null) {
			$this->assignation['node'] = $node;
			$this->assignation['source'] = $node->getNodeSources()->first();
			$this->assignation['translation'] = $translation;
			
			/*
			 * Handle translation form
			 */
			$translation_form = $this->buildTranslateForm( $node );
			if ($translation_form !== null) {
				$translation_form->handleRequest();

				if ($translation_form->isValid()) {

					try {
				 		$this->translateNode($translation_form->getData(), $node);
				 		$msg = $this->getTranslator()->trans('node.translated', array(
				 			'%name%'=>$node->getNodeName()
				 		));
				 		$request->getSession()->getFlashBag()->add('confirm', $msg);
	 					$this->getLogger()->info($msg);
					}
					catch( EntityAlreadyExistsException $e ){
						$request->getSession()->getFlashBag()->add('error', $e->getMessage());
	 					$this->getLogger()->warning($e->getMessage());
					}
			 		/*
			 		 * Force redirect to avoid resending form when refreshing page
			 		 */
			 		$response = new RedirectResponse(
						Kernel::getInstance()->getUrlGenerator()->generate(
							'nodesEditSourcePage',
							array('node_id' => $node->getId(), 'translation_id'=>$translation_form->getData()['translation_id'])
						)
					);
					$response->prepare($request);
					return $response->send();
				}
				$this->assignation['translation_form'] = $translation_form->createView();
			}

			/*
			 * Handle main form
			 */
			$form = $this->buildEditForm( $node );
			$form->handleRequest();

			if ($form->isValid()) {
				try {
		 			$this->editNode($form->getData(), $node);
		 			$msg = $this->getTranslator()->trans('node.updated', array(
			 			'%name%'=>$node->getNodeName()
			 		));
		 			$request->getSession()->getFlashBag()->add('confirm', $msg);
	 				$this->getLogger()->info($msg);
				}
		 		catch( EntityAlreadyExistsException $e ){
					$request->getSession()->getFlashBag()->add('error', $e->getMessage());
	 				$this->getLogger()->warning($e->getMessage());
				}
		 		/*
		 		 * Force redirect to avoid resending form when refreshing page
		 		 */
		 		$response = new RedirectResponse(
					Kernel::getInstance()->getUrlGenerator()->generate(
						'nodesEditPage',
						array('node_id' => $node->getId())
					)
				);
				$response->prepare($request);

				return $response->send();
			}
			$this->assignation['form'] = $form->createView();

			return new Response(
				$this->getTwig()->render('nodes/edit.html.twig', $this->assignation),
				Response::HTTP_OK,
				array('content-type' => 'text/html')
			);
		}
		

		return $this->throw404();
	}

	/**
	 * Return an edition form for requested node
	 * 
	 * @param  integer $node_id        [description]
	 * @param  integer $translation_id [description]
	 * @return Symfony\Component\HttpFoundation\Response
	 */
	public function editSourceAction( Request $request, $node_id, $translation_id = null )
	{
		$translation = Kernel::getInstance()->em()
				->getRepository('RZ\Renzo\Core\Entities\Translation')
				->findOneBy(array('defaultTranslation'=>true));
		if ($translation_id !== null) {
			$translation = Kernel::getInstance()->em()
				->find('RZ\Renzo\Core\Entities\Translation', (int)$translation_id);
		}

		if ($translation !== null) {

			$node = Kernel::getInstance()->em()
				->getRepository('RZ\Renzo\Core\Entities\Node')
				->findWithTranslation((int)$node_id, $translation);

			if ($node !== null && 
				$translation !== null) {

				$source = $node->getNodeSources()->first();

				$this->assignation['translation'] = $translation;
				$this->assignation['available_translations'] = $node->getHandler()->getAvailableTranslations();
				$this->assignation['node'] = $node;
				$this->assignation['source'] = $source;
				
				/*
				 * Form
				 */
				$form = $this->buildEditSourceForm( $node, $source );
				$form->handleRequest();

				if ($form->isValid()) {
			 		$this->editNodeSource($form->getData(), $source);

			 		$msg = $this->getTranslator()->trans('node_source.updated', array(
			 			'%node_source%'=>$source->getNode()->getNodeName(), 
			 			'%translation%'=>$source->getTranslation()->getName()
			 		));
			 		$request->getSession()->getFlashBag()->add('confirm',$msg);
	 				$this->getLogger()->info($msg);
			 		/*
			 		 * Force redirect to avoid resending form when refreshing page
			 		 */
			 		$response = new RedirectResponse(
						Kernel::getInstance()->getUrlGenerator()->generate(
							'nodesEditSourcePage',
							array('node_id' => $node->getId(), 'translation_id'=>$translation->getId())
						)
					);
					$response->prepare($request);

					return $response->send();
				}

				$this->assignation['form'] = $form->createView();

				return new Response(
					$this->getTwig()->render('nodes/editSource.html.twig', $this->assignation),
					Response::HTTP_OK,
					array('content-type' => 'text/html')
				);
			}
		}

		return $this->throw404();
	}

	/**
	 * Return tags form for requested node
	 * 
	 * @param  integer $node_id        [description]
	 * @param  integer $translation_id [description]
	 * @return Symfony\Component\HttpFoundation\Response
	 */
	public function editTagsAction( Request $request, $node_id )
	{
		$translation = Kernel::getInstance()->em()
				->getRepository('RZ\Renzo\Core\Entities\Translation')
				->findOneBy(array('defaultTranslation'=>true));

		if ($translation !== null) {

			$node = Kernel::getInstance()->em()
				->getRepository('RZ\Renzo\Core\Entities\Node')
				->findWithTranslation((int)$node_id, $translation);

			if ($node !== null && 
				$translation !== null) {

				$source = $node->getNodeSources()->first();

				$this->assignation['translation'] = $translation;
				$this->assignation['node'] = 		$node;
				$this->assignation['source'] = 		$source;
				
				$form = $this->buildEditTagsForm( $node );

				$form->handleRequest();

				if ($form->isValid()) {
			 		$tag = $this->addNodeTag($form->getData(), $node);

			 		$msg = $this->getTranslator()->trans('node.tag_linked', array(
			 			'%node%'=>$node->getNodeName(), 
			 			'%tag%'=>$tag->getDefaultTranslatedTag()->getName()
			 		));
			 		$request->getSession()->getFlashBag()->add('confirm', $msg);
	 				$this->getLogger()->info($msg);
			 		/*
			 		 * Force redirect to avoid resending form when refreshing page
			 		 */
			 		$response = new RedirectResponse(
						Kernel::getInstance()->getUrlGenerator()->generate(
							'nodesEditTagsPage',
							array('node_id' => $node->getId())
						)
					);
					$response->prepare($request);

					return $response->send();
				}

				$this->assignation['form'] = $form->createView();

				return new Response(
					$this->getTwig()->render('nodes/editTags.html.twig', $this->assignation),
					Response::HTTP_OK,
					array('content-type' => 'text/html')
				);
			}
		}
		return $this->throw404();
	}



	/**
	 * Handle node creation pages
	 * @param [type] $node_type_id   [description]
	 * @param [type] $translation_id [description]
	 */
	public function addAction( Request $request, $node_type_id, $translation_id = null )
	{	
		$type = Kernel::getInstance()->em()
				->find('RZ\Renzo\Core\Entities\NodeType', $node_type_id);

		$translation = Kernel::getInstance()->em()
				->getRepository('RZ\Renzo\Core\Entities\Translation')
				->findOneBy(array('defaultTranslation'=>true));

		if ($translation_id != null) {
			$translation = Kernel::getInstance()->em()
				->find('RZ\Renzo\Core\Entities\Translation', (int)$translation_id);
		}

		if ($type !== null &&
			$translation !== null) {

			$form = $this->getFormFactory()
						->createBuilder()
						->add('nodeName', 'text', array(
							'constraints' => array(
								new NotBlank()
							)
						))
						->getForm();
			$form->handleRequest();

			if ($form->isValid()) {

				try {
					$node = $this->createNode($form->getData(), $type, $translation);

					$msg = $this->getTranslator()->trans('node.created', array('%name%'=>$node->getNodeName()));
					$request->getSession()->getFlashBag()->add('confirm', $msg);
	 				$this->getLogger()->info($msg);

					$response = new RedirectResponse(
						Kernel::getInstance()->getUrlGenerator()->generate(
							'nodesEditPage',
							array('node_id' => $node->getId())
						)
					);
					$response->prepare($request);
					return $response->send();
				}
				catch(EntityAlreadyExistsException $e) {

					$request->getSession()->getFlashBag()->add('error', $e->getMessage());
	 				$this->getLogger()->warning($e->getMessage());

					$response = new RedirectResponse(
						Kernel::getInstance()->getUrlGenerator()->generate(
							'nodesAddPage',
							array('node_type_id' => $node_type_id, 'translation_id' => $translation_id)
						)
					);
					$response->prepare($request);
					return $response->send();
				}
			}

			$this->assignation['translation'] = $translation;
			$this->assignation['form'] = $form->createView();
			$this->assignation['type'] = $type;

			return new Response(
				$this->getTwig()->render('nodes/add.html.twig', $this->assignation),
				Response::HTTP_OK,
				array('content-type' => 'text/html')
			);
		}else {
			return $this->throw404();
		}
	}

	/**
	 * Handle node creation pages
	 * @param [type] $node_type_id   [description]
	 * @param [type] $translation_id [description]
	 */
	public function addChildAction( Request $request, $node_id, $translation_id = null )
	{	
		$translation = Kernel::getInstance()->em()
				->getRepository('RZ\Renzo\Core\Entities\Translation')
				->findOneBy(array('defaultTranslation'=>true));

		if ($translation_id != null) {
			$translation = Kernel::getInstance()->em()
				->find('RZ\Renzo\Core\Entities\Translation', (int)$translation_id);
		}
		$parentNode = Kernel::getInstance()->em()
			->find('RZ\Renzo\Core\Entities\Node', (int)$node_id);

		if ($translation !== null && 
			$parentNode !== null) {

			$form = $this->buildAddChildForm( $parentNode, $translation );
			$form->handleRequest();

			if ($form->isValid()) {

				try {
					$node = $this->createChildNode($form->getData(), $parentNode, $translation);

					$msg = $this->getTranslator()->trans('node.created', array('%name%'=>$node->getNodeName()));
					$request->getSession()->getFlashBag()->add('confirm', $msg);
	 				$this->getLogger()->info($msg);

					$response = new RedirectResponse(
						Kernel::getInstance()->getUrlGenerator()->generate(
							'nodesEditPage',
							array('node_id' => $node->getId())
						)
					);
					$response->prepare($request);
					return $response->send();
				}
				catch(EntityAlreadyExistsException $e) {

					$request->getSession()->getFlashBag()->add('error', $e->getMessage());
	 				$this->getLogger()->warning($e->getMessage());

					$response = new RedirectResponse(
						Kernel::getInstance()->getUrlGenerator()->generate(
							'nodesAddChildPage',
							array('node_id' => $node_id, 'translation_id' => $translation_id)
						)
					);
					$response->prepare($request);
					return $response->send();
				}
			}

			$this->assignation['translation'] = $translation;
			$this->assignation['form'] = $form->createView();
			$this->assignation['parentNode'] = $parentNode;

			return new Response(
				$this->getTwig()->render('nodes/add.html.twig', $this->assignation),
				Response::HTTP_OK,
				array('content-type' => 'text/html')
			);
		}else {
			return $this->throw404();
		}
	}

	/**
	 * Return an deletion form for requested node
	 * @return Symfony\Component\HttpFoundation\Response
	 */
	public function deleteAction( Request $request, $node_id )
	{
		$node = Kernel::getInstance()->em()
			->find('RZ\Renzo\Core\Entities\Node', (int)$node_id);

		if ($node !== null) {
			$this->assignation['node'] = $node;
			
			$form = $this->buildDeleteForm( $node );

			$form->handleRequest();

			if ($form->isValid() && 
				$form->getData()['node_id'] == $node->getId() ) {

				$node->getHandler()->removeWithChildrenAndAssociations();

				$msg = $this->getTranslator()->trans('node.deleted', array('%name%'=>$node->getNodeName()));
				$request->getSession()->getFlashBag()->add('confirm', $msg);
	 			$this->getLogger()->info($msg);
		 		/*
		 		 * Force redirect to avoid resending form when refreshing page
		 		 */
		 		$response = new RedirectResponse(
					Kernel::getInstance()->getUrlGenerator()->generate('nodesHomePage')
				);
				$response->prepare($request);

				return $response->send();
			}

			$this->assignation['form'] = $form->createView();

			return new Response(
				$this->getTwig()->render('nodes/delete.html.twig', $this->assignation),
				Response::HTTP_OK,
				array('content-type' => 'text/html')
			);
		}
		else {
			return $this->throw404();
		}
	}

	/**
	 * 
	 * @param  array $data 
	 * @return RZ\Renzo\Core\Entities\Node
	 */
	private function createNode( $data, NodeType $type, Translation $translation )
	{
		if ($this->urlAliasExists( StringHandler::slugify($data['nodeName']) )) {
			$msg = $this->getTranslator()->trans('node.no_creation.url_alias.already_exists', array('%name%'=>$data['nodeName']));
			throw new EntityAlreadyExistsException($msg, 1);
		}

		try {
			$node = new Node( $type );
			$node->setNodeName($data['nodeName']);
			Kernel::getInstance()->em()->persist($node);

			$sourceClass = "GeneratedNodeSources\\".$type->getSourceEntityClassName();
			$source = new $sourceClass($node, $translation);
			Kernel::getInstance()->em()->persist($source);
			Kernel::getInstance()->em()->flush();
			return $node;
		}
		catch( \Exception $e ){
			$msg = $this->getTranslator()->trans('node.no_creation.already_exists', array('%name%'=>$node->getNodeName()));
			throw new EntityAlreadyExistsException($msg, 1);
		}
	}

	/**
	 * 
	 * @param  array $data 
	 * @return RZ\Renzo\Core\Entities\Node
	 */
	private function createChildNode( $data, Node $parentNode, Translation $translation )
	{
		if ($this->urlAliasExists( StringHandler::slugify($data['nodeName']) )) {
			$msg = $this->getTranslator()->trans('node.no_creation.url_alias.already_exists', array('%name%'=>$data['nodeName']));
			throw new EntityAlreadyExistsException($msg, 1);
		}
		$type = null;

		if (!empty($data['node_type_id'])) {
			$type = Kernel::getInstance()->em()
						->find('RZ\Renzo\Core\Entities\NodeType', (int)$data['node_type_id']);
		}
		if ($type === null) {
			throw new \Exception("Cannot create a node without a valid node-type", 1);
		}
		if ($data['parent_id'] != $parentNode->getId()) {
			throw new \Exception("Requested parent node does not match form values", 1);
		}

		try {
			$node = new Node( $type );
			$node->setParent($parentNode);
			$node->setNodeName($data['nodeName']);
			Kernel::getInstance()->em()->persist($node);

			$sourceClass = "GeneratedNodeSources\\".$type->getSourceEntityClassName();
			$source = new $sourceClass($node, $translation);
			Kernel::getInstance()->em()->persist($source);
			Kernel::getInstance()->em()->flush();
			return $node;
		}
		catch( \Exception $e ){
			$msg = $this->getTranslator()->trans('node.no_creation.already_exists', array('%name%'=>$node->getNodeName()));
			throw new EntityAlreadyExistsException($msg, 1);
		}
	}

	private function urlAliasExists( $name )
	{
		return (boolean)Kernel::getInstance()->em()
			->getRepository('RZ\Renzo\Core\Entities\UrlAlias')
			->exists( $name );
	}
	private function nodeNameExists( $name )
	{
		return (boolean)Kernel::getInstance()->em()
			->getRepository('RZ\Renzo\Core\Entities\Node')
			->exists( $name );
	}

	/**
	 * Edit node base parameters
	 * 
	 * @param  array $data Form data
	 * @param  Node   $node [description]
	 * @return void
	 */
	private function editNode( $data, Node $node)
	{	
		$testingNodeName = StringHandler::slugify($data['nodeName']);
		if ($testingNodeName != $node->getNodeName() && 
				($this->nodeNameExists($testingNodeName) || 
				$this->urlAliasExists($testingNodeName))) {

			$msg = $this->getTranslator()->trans('node.no_update.already_exists', array('%name%'=>$data['nodeName']));
			throw new EntityAlreadyExistsException($msg , 1);
		}
		foreach ($data as $key => $value) {
			$setter = 'set'.ucwords($key);
			$node->$setter( $value );
		}

		Kernel::getInstance()->em()->flush();
	}

	/**
	 * Link a node with a tag 
	 * 
	 * @param  array $data Form data
	 * @param  Node   $node [description]
	 * @return Tag $linkedTag
	 */
	private function addNodeTag($data, Node $node)
	{
		$tag = Kernel::getInstance()->em()
				->getRepository('RZ\Renzo\Core\Entities\Tag')
				->findWithDefaultTranslation($data['tag_id']);

		$node->getTags()->add($tag);
		Kernel::getInstance()->em()->flush();

		return $tag;
	}

	/**
	 * Create a new node-source for given translation
	 * 
	 * 
	 * @param  array $data Form data
	 * @param  Node   $node [description]
	 * @return void
	 */
	private function translateNode( $data, Node $node )
	{
		$sourceClass = "GeneratedNodeSources\\".$node->getNodeType()->getSourceEntityClassName();
		$new_translation = Kernel::getInstance()->em()
				->find('RZ\Renzo\Core\Entities\Translation', (int)$data['translation_id']);


		$source = new $sourceClass($node, $new_translation);

		Kernel::getInstance()->em()->persist($source);
		Kernel::getInstance()->em()->flush();
	}

	/**
	 * Edit node source parameters
	 * 
	 * 
	 * @param  array $data Form data
	 * @param  $nodeSource
	 * @return void    
	 */
	private function editNodeSource( $data, $nodeSource )
	{
		$fields = $nodeSource->getNode()->getNodeType()->getFields();
		foreach ($fields as $field) {
			if (isset($data[$field->getName()])) {

				$setter = $field->getSetterName();
				$nodeSource->$setter( $data[$field->getName()] );
			}
		}

		Kernel::getInstance()->em()->flush();
	}

	

	private function buildTranslateForm( Node $node )
	{
		$translations = $node->getHandler()->getUnavailableTranslations();
		$choices = array();

		foreach ($translations as $translation) {
			$choices[$translation->getId()] = $translation->getName();
		}

		if ($translations !== null && count($choices) > 0) {

			$builder = $this->getFormFactory()
				->createBuilder('form')
				->add('node_id', 'hidden', array(
					'data' => $node->getId(),
					'constraints' => array(
						new NotBlank()
					)
				))
				->add('translation_id', 'choice', array(
					'choices' => $choices,
					'required' => true
				))
			;

			return $builder->getForm();
		}
		else {
			return null;
		}
	}

	/**
	 * 
	 * @param  Node   $parentNode 
	 * @return Symfony\Component\Form\Forms
	 */
	private function buildAddChildForm( Node $parentNode )
	{
		$defaults = array(
			
		);
		$builder = $this->getFormFactory()
			->createBuilder('form', $defaults)
			->add('nodeName', 'text', array(
				'constraints' => array(
					new NotBlank()
				)
			))
			->add('parent_id', 'hidden', array(
				'data'=>(int)$parentNode->getId(),
				'constraints' => array(
					new NotBlank()
				)
			))
			->add('node_type_id', new \RZ\Renzo\CMS\Forms\NodeTypesType())
		;

		return $builder->getForm();
	}

	/**
	 * 
	 * @param  Node   $node 
	 * @return Symfony\Component\Form\Forms
	 */
	private function buildEditForm( Node $node )
	{
		$fields = $node->getNodeType()->getFields();

		$defaults = array(
			'nodeName' =>  $node->getNodeName(),
			'visible' =>   $node->isVisible(),
			'locked' =>    $node->isLocked(),
			'published' => $node->isPublished(),
			'archived' =>  $node->isArchived(),
		);
		$builder = $this->getFormFactory()
					->createBuilder('form', $defaults)
					->add('nodeName', 'text', array(
						'constraints' => array(
							new NotBlank()
						)
					))
					->add('visible',   'checkbox', array('required' => false))
					->add('locked',    'checkbox', array('required' => false))
					->add('published', 'checkbox', array('required' => false))
					->add('archived',  'checkbox', array('required' => false));

		return $builder->getForm();
	}

	/**
	 * 
	 * @param  Node   $node 
	 * @return Symfony\Component\Form\Forms
	 */
	private function buildEditTagsForm( Node $node )
	{
		$defaults = array(
			'node_id' =>  $node->getId()
		);
		$builder = $this->getFormFactory()
					->createBuilder('form', $defaults)
					->add('node_id', 'hidden', array(
						'data' => $node->getId(),
						'constraints' => array(
							new NotBlank()
						)
					))
					->add('tag_id', new \RZ\Renzo\CMS\Forms\TagsType() );

		return $builder->getForm();
	}

	/**
	 * 
	 * @param  Node  $node
	 * @param  NodesSources $source
	 * @return Symfony\Component\Form\Forms
	 */
	private function buildEditSourceForm( Node $node, $source )
	{
		$fields = $node->getNodeType()->getFields();
		/*
		 * Create source default values
		 */
		$sourceDefaults = array();
		foreach ($fields as $field) {
			$getter = $field->getGetterName();
			$sourceDefaults[$field->getName()] = $source->$getter();
		}	

		/*
		 * Create subform for source
		 */
		$sourceBuilder = $this->getFormFactory()
					->createNamedBuilder('source','form', $sourceDefaults);
		foreach ($fields as $field) {
			$sourceBuilder->add(
				$field->getName(), 
				static::getFormTypeFromFieldType( $field ), 
				array(
					'label'  => $field->getLabel(),
					'required' => false
				)
			);
		}
		return $sourceBuilder->getForm();
	}

	/**
	 * 
	 * @param  string $type
	 * @return AbstractType
	 */
	public static function getFormTypeFromFieldType( NodeTypeField $field )
	{
		switch ($field->getType()) {
			case NodeTypeField::MARKDOWN_T:
				return new \RZ\Renzo\CMS\Forms\MarkdownType();
			
			default:
				return NodeTypeField::$typeToForm[$field->getType()];
		}
	}

	/**
	 * 
	 * @param  Node   $node 
	 * @return Symfony\Component\Form\Forms
	 */
	private function buildDeleteForm( Node $node )
	{
		$builder = $this->getFormFactory()
			->createBuilder('form')
			->add('node_id', 'hidden', array(
				'data' => $node->getId(),
				'constraints' => array(
					new NotBlank()
				)
			))
		;

		return $builder->getForm();
	}
}