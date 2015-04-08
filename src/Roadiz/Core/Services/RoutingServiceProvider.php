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
 * @file RoutingServiceProvider.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Services;

use Pimple\Container;
use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Core\Routing\MixedUrlMatcher;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Security\Http\HttpUtils;

/**
 * Register routing services for dependency injection container.
 */
class RoutingServiceProvider implements \Pimple\ServiceProviderInterface
{
    public function register(Container $container)
    {
        $container['resolver'] = function ($c) {
            return new ControllerResolver();
        };
        $container['httpKernel'] = function ($c) {
            return new HttpKernel($c['dispatcher'], $c['resolver']);
        };
        $container['urlMatcher'] = function ($c) {
            return new MixedUrlMatcher($c['requestContext']);
        };
        $container['urlGenerator'] = function ($c) {
            return new \GlobalUrlGenerator($c['requestContext']);
        };
        $container['httpUtils'] = function ($c) {
            return new HttpUtils($c['urlGenerator'], $c['urlMatcher']);
        };

        if (isset($container['config']['install']) &&
            true === $container['config']['install']) {
            /*
             * Get Install routes
             */
            $container['routeCollection'] = function ($c) {

                $installClassname = Kernel::INSTALL_CLASSNAME;
                $feCollection = $installClassname::getRoutes();
                $rCollection = new RouteCollection();
                $rCollection->addCollection($feCollection);

                $installClassname::setupDependencyInjection($c);

                return $rCollection;
            };
        } else {
            /*
             * Get App routes
             */
            $container['routeCollection'] = function ($c) {

                $c['stopwatch']->start('routeCollection');
                $rCollection = new RouteCollection();

                /*
                 * Add Assets controller routes
                 */
                $rCollection->addCollection(
                    \RZ\Roadiz\CMS\Controllers\AssetsController::getRoutes()
                );

                /*
                 * Add Entry points controller routes
                 */
                $rCollection->addCollection(
                    \RZ\Roadiz\CMS\Controllers\EntryPointsController::getRoutes()
                );

                /*
                 * Add Backend routes
                 */
                $beClass = $c['backendClass'];
                $cmsCollection = $beClass::getRoutes();
                if ($cmsCollection !== null) {
                    $rCollection->addCollection($cmsCollection);
                }

                /*
                 * Add Frontend routes
                 *
                 * return 'RZ\Roadiz\CMS\Controllers\FrontendController';
                 */
                foreach ($c['frontendThemes'] as $theme) {
                    $feClass = $theme->getClassName();
                    $feCollection = $feClass::getRoutes();
                    $feBackendCollection = $feClass::getBackendRoutes();

                    if ($feCollection !== null) {
                        // set host pattern if defined
                        if ($theme->getHostname() != '*' &&
                            $theme->getHostname() != '') {
                            $feCollection->setHost($theme->getHostname());
                        }
                        /*
                         * Add a global prefix on theme static routes
                         */
                        if ($theme->getRoutePrefix() != '') {
                            $feCollection->addPrefix($theme->getRoutePrefix());
                        }
                        $rCollection->addCollection($feCollection);
                    }
                    if ($feBackendCollection !== null) {
                        /*
                         * Do not prefix or hostname admin routes.
                         */
                        $rCollection->addCollection($feBackendCollection);
                    }
                }

                $c['stopwatch']->stop('routeCollection');
                return $rCollection;
            };
        }

        return $container;
    }
}
