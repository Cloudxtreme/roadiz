<?php 
namespace Themes\Rozier\Controllers;

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\Node;
use RZ\Renzo\Core\Entities\NodeType;
use RZ\Renzo\Core\Entities\NodeTypeField;
use RZ\Renzo\Core\Entities\Translation;
use RZ\Renzo\CMS\Controllers\FrontendController;
use Themes\Rozier\RozierApp;


use RZ\Renzo\Core\Exceptions\EntityAlreadyExistsException;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Symfony\Component\Form\Forms;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

/**
 * Redirection controller use to update database schema 
 * 
 */
class SchemaController extends RozierApp {

	/**
	 * No preparation for this blind controller
	 * @return $this
	 */
	public function prepareBaseAssignation()
	{
		return $this;
	}

	public function updateNodeTypesSchemaAction( Request $request, $_token )
	{	
		if (static::$csrfProvider->isCsrfTokenValid(static::SCHEMA_TOKEN_INTENTION, $_token)) {

			\RZ\Renzo\Console\SchemaCommand::updateSchema();

			$msg = $this->getTranslator()->trans('database.schema.updated');
			$request->getSession()->getFlashBag()->add('confirm', $msg);
			$this->getLogger()->info($msg);
		}
		else {
			$msg = $this->getTranslator()->trans('database.schema.cannot_updated');
			$request->getSession()->getFlashBag()->add('error', $msg);
			$this->getLogger()->error($msg);
		}
		/*
 		 * Redirect to update schema page
 		 */
 		$response = new RedirectResponse(
			Kernel::getInstance()->getUrlGenerator()->generate(
				'nodeTypesHomePage'
			)
		);
		$response->prepare($request);

		return $response->send();
	}

	public function updateNodeTypeFieldsSchemaAction( Request $request, $_token, $node_type_id )
	{	
		if (static::$csrfProvider->isCsrfTokenValid(static::SCHEMA_TOKEN_INTENTION, $_token)) {
			\RZ\Renzo\Console\SchemaCommand::updateSchema();

			$msg = $this->getTranslator()->trans('database.schema.updated');
			$request->getSession()->getFlashBag()->add('confirm', $msg);
			$this->getLogger()->info($msg);
		}
		else {
			$msg = $this->getTranslator()->trans('database.schema.cannot_updated');
			$request->getSession()->getFlashBag()->add('error', $msg);
			$this->getLogger()->error($msg);
		}
		/*
 		 * Redirect to update schema page
 		 */
 		$response = new RedirectResponse(
			Kernel::getInstance()->getUrlGenerator()->generate(
				'nodeTypeFieldsListPage', 
				array(
					'node_type_id' => $node_type_id
				)
			)
		);
		$response->prepare($request);

		return $response->send();
	}
}