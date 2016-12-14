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
 * @file YamlConfigurationServiceProvider.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Services;

use Pimple\Container;
use RZ\Roadiz\Config\YamlConfigurationHandler;
use RZ\Roadiz\Core\Kernel;

/**
 * Register configuration services for dependency injection container.
 */
class YamlConfigurationServiceProvider extends AbstractConfigurationServiceProvider
{
    /**
     * @param Container $container [description]
     * @return Container
     */
    public function register(Container $container)
    {
        parent::register($container);

        $container['config_path'] = function ($c) {
            /** @var Kernel $kernel */
            $kernel = $c['kernel'];
            $configDir = $kernel->getRootDir() . '/conf';
            if ($kernel->getEnvironment() != 'prod') {
                $configName = 'config_' . $kernel->getEnvironment() . '.yml';

                if (file_exists($configDir . '/' . $configName)) {
                    return $configDir . '/' . $configName;
                }
            }

            return $configDir . '/config.yml';
        };

        /*
         * Inject app config
         */
        $container['config_handler'] = function ($c) {
            /** @var Kernel $kernel */
            $kernel = $c['kernel'];

            return new YamlConfigurationHandler(
                $kernel->getCacheDir(),
                $kernel->isDebug(),
                $c['config_path']
            );
        };

        /*
         * Inject app config
         */
        $container['config'] = function ($c) {
            /** @var YamlConfigurationHandler $configuration */
            $configuration = $c['config_handler'];

            return $configuration->load();
        };

        return $container;
    }
}
