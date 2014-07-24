<?php 
namespace Themes\DefaultTheme\Controllers;

use RZ\Renzo\Core\Entities\Node;
use RZ\Renzo\Core\Entities\Translation;
use RZ\Renzo\Core\Utils\StringHandler;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
/**
 * 
 * Frontend controller to handle node request
 * This class must be inherited in order to create a new theme
 * 
 */
class PageController extends DefaultApp {

	/**
	 * Default action for any Page node
	 * @param  Symfony\Component\HttpFoundation\Request
	 * @param  RZ\Renzo\Core\Entities\Node $node Requested node for given URL
	 * @param  RZ\Renzo\Core\Entities\Translation $translation
	 * @return Symfony\Component\HttpFoundation\Response
	 */
	public function indexAction( Request $request, Node $node = null, Translation $translation = null)
	{
		$this->prepareThemeAssignation($node, $translation);

		$this->assignation['imageFilter'] = array(
			'width'=>300,
			'crop'=>'16x9'
		);
		
		return new Response(
			$this->getTwig()->render('types/page.html.twig', $this->assignation),
			Response::HTTP_OK,
			array('content-type' => 'text/html')
		);
	}
}