<?php 

namespace RZ\Renzo\Core\Utils;


use RZ\Renzo\Core\Kernel;  

use Symfony\Component\HttpFoundation\RedirectResponse;  
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;  
use Symfony\Component\EventDispatcher\EventDispatcher;  
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;  

use Symfony\Bridge\Twig\Extension\RoutingExtension;
use Symfony\Bridge\Twig\Form\TwigRenderer;
use Symfony\Bridge\Twig\Form\TwigRendererEngine;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;


/**
* 		
*/
class DebugPanel implements EventSubscriberInterface
{
	private $twig = null;

	public function getTwig() { return $this->twig; }

	/**
	 * @return array
	 */
	public static function getSubscribedEvents()	{
		return array('kernel.response' => 'onKernelResponse');
	}

	public function onKernelResponse(FilterResponseEvent $event)
	{
		$response = $event->getResponse();

		if (strpos($response->getContent(), '</body>') !== false) {
			
			$this->initializeTwig();

			$content = str_replace('</body>', $this->getDebugView()."</body>", $response->getContent());

			$response->setContent($content);
			$event->setResponse($response);
		}
	}

	private function getDebugView()
	{
		Kernel::getInstance()->getStopwatch()->stop('global');
		
		$assignation = array(
			'stopwatch'=>Kernel::getInstance()->getStopwatch() 
		);
		return $this->getTwig()->render('debug-panel.html.twig', $assignation);
	}

	/**
	 * Create a Twig Environment instance
	 */
	private function initializeTwig()
	{
		$cacheDir = RENZO_ROOT.'/cache/debug_panel/twig_cache';

		if (Kernel::getInstance()->isBackendDebug()) {
			try {
				$fs = new Filesystem();
				$fs->remove(array($cacheDir));
			} catch (IOExceptionInterface $e) {
			    echo "An error occurred while deleting backend twig cache directory: ".$e->getPath();
			}
		}

		$loader = new \Twig_Loader_Filesystem(array(
			RENZO_ROOT.'/src/Renzo/Core/Resources/Templates', // Theme templates
		));
		$this->twig = new \Twig_Environment($loader, array(
		    'cache' => $cacheDir,
		));

		//RoutingExtension
		$this->twig->addExtension(
		    new RoutingExtension(Kernel::getInstance()->getUrlGenerator())
		);

		return $this;
	}
}