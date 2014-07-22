<?php 

namespace Themes\DefaultTheme\Controllers;

use RZ\Renzo\CMS\Controllers\FrontendController;

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\Node;
use RZ\Renzo\Core\Entities\Translation;
use RZ\Renzo\Core\Utils\StringHandler;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\Routing\Exception\ResourceNotFoundException;

/**
* 
*/
class DefaultController extends FrontendController
{
	protected static $specificNodesControllers = array(
		'home',
		// Put here your node which need a specific controller
		// instead of a node-type controller
	);

	/**
	 * Default action for any node URL
	 * @param  Symfony\Component\HttpFoundation\Request
	 * @param  RZ\Renzo\Core\Entities\Node $node Requested node for given URL
	 * @param  RZ\Renzo\Core\Entities\Translation $translation
	 * @return Symfony\Component\HttpFoundation\Response
	 */
	public function indexAction( Request $request, Node $node = null, Translation $translation = null)
	{
		$this->prepareThemeAssignation($node, $translation);

		//	Main node based routing method
		return $this->handle( $request );
	}

	/**
	 * Default action for default URL (homepage)
	 * @param  Symfony\Component\HttpFoundation\Request
	 * @param  RZ\Renzo\Core\Entities\Node $node Requested node for given URL
	 * @param  RZ\Renzo\Core\Entities\Translation $translation
	 * @return Symfony\Component\HttpFoundation\Response
	 */
	public function homeAction( Request $request, Node $node = null, Translation $translation = null)
	{	
		if ($node === null) {
			$node = Kernel::getInstance()->em()
					->getRepository('RZ\Renzo\Core\Entities\Node')
					->findOneBy(array('home'=>true));
		}
		$this->prepareThemeAssignation($node, $translation);

		return new Response(
			$this->getTwig()->render('home.html.twig', $this->assignation),
			Response::HTTP_OK,
			array('content-type' => 'text/html')
		);
	}

	protected function prepareThemeAssignation( Node $node = null, Translation $translation = null )
	{
		$this->storeNodeAndTranslation($node, $translation);
		$this->assignation['navigation'] = $this->assignMainNavigation();

		$this->assignation['headerImageFilter'] = array(
			'width'=>1024,
			'crop'=>'1024x200'
		);
	}

	protected function assignMainNavigation()
	{
		$parent = Kernel::getInstance()->em()
					->getRepository('RZ\Renzo\Core\Entities\Node')
					->findOneBy(array('home'=>true));

		if ($this->translation === null) {
			$this->translation = Kernel::getInstance()->em()
					->getRepository('RZ\Renzo\Core\Entities\Translation')
					->findOneBy(array('defaultTranslation'=>true));
		}
		if ($parent !== null) {
			return Kernel::getInstance()->em()
					->getRepository('RZ\Renzo\Core\Entities\Node')
					->findByParentWithTranslation($parent, $this->translation);
		}
		return null;
	}
}