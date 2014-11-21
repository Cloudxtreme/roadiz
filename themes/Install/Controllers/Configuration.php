<?php
/*
 * Copyright © 2014, REZO ZERO
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
 * Except as contained in this notice, the name of the REZO ZERO shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from the REZO ZERO SARL.
 *
 * @file Configuration.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace Themes\Install\Controllers;

/**
* Configuration class
*/
class Configuration
{
    protected $configuration;

    /**
     * Configuration constructor
     */
    public function __construct()
    {
        // Try to load existant configuration
        if (false === $this->loadFromFile(RENZO_ROOT . '/conf/config.json')) {
            if (false === $this->loadFromFile(RENZO_ROOT . '/conf/config.default.json')) {
                $this->setConfiguration($this->getDefaultConfiguration());
            }
        }
    }

    /**
     * @return array
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * @param array $configuration
     *
     * @return arry $configuration
     */
    public function setConfiguration($configuration)
    {
        $this->configuration = $configuration;

        return $this;
    }

    /**
     * @return array
     */
    public function getDefaultConfiguration()
    {
        return array(
            "appNamespace" => "chooseAnUniqueNameForYourApp",
            "install" => true,
            "devMode" => true,
            "doctrine" => array(
                "driver" =>   "pdo_mysql",
                "host" =>     "localhost"
            ),
            "security" => array(
                "secret" => "change#this#secret#very#important"
            ),
            "entities" => array(
                "src/Roadiz/Core/Entities",
                "src/Roadiz/Core/AbstractEntities",
                "sources/GeneratedNodeSources"
            )
        );
    }

    /**
     * @param string $file Absolute path to conf file
     *
     * @return boolean
     */
    public function loadFromFile($file)
    {
        if (file_exists($file)) {
            $conf = json_decode(file_get_contents($file), true);

            if ($conf !== null && $conf !== false) {
                $this->setConfiguration($conf);

                return true;
            }
        }

        return false;
    }

    /**
     * @return void
     */
    public function writeConfiguration()
    {
        $writePath = RENZO_ROOT . '/conf/config.json';

        if (file_exists($writePath)) {
            unlink($writePath);
        }

        file_put_contents(
            $writePath,
            json_encode(
                $this->getConfiguration(),
                JSON_PRETTY_PRINT | JSON_NUMERIC_CHECK | JSON_UNESCAPED_UNICODE
            )
        );
    }
}
