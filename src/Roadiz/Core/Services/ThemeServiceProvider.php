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
 * @file ThemeServiceProvider.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Services;

use Pimple\Container;
use RZ\Roadiz\Core\Entities\Theme;

/**
 * Register Theme services for dependency injection container.
 */
class ThemeServiceProvider implements \Pimple\ServiceProviderInterface
{
    public function register(Container $container)
    {
        $container['backendClass'] = function ($c) {
            $theme = $c['backendTheme'];

            if ($theme !== null) {
                return $theme->getClassName();
            }

            return 'RZ\Roadiz\CMS\Controllers\BackendController';
        };

        $container['backendTheme'] = function ($c) {

            if (isset($c['config']['install']) &&
                false === $c['config']['install']) {
                return $c['em']->getRepository('RZ\Roadiz\Core\Entities\Theme')
                                 ->findAvailableBackend();
            } else {
                return null;
            }
        };

        $container['frontendThemes'] = function ($c) {

            if (isset($c['config']['install']) &&
                false === $c['config']['install']) {
                $themes = $c['em']->getRepository('RZ\Roadiz\Core\Entities\Theme')
                                  ->findAvailableFrontends();


                if (count($themes) < 1) {
                    $defaultTheme = new Theme();
                    $defaultTheme->setClassName('RZ\Roadiz\CMS\Controllers\FrontendController');
                    $defaultTheme->setAvailable(true);

                    return array(
                        $defaultTheme
                    );
                } else {
                    return $themes;
                }
            } else {
                return array();
            }
        };

        return $container;
    }
}
