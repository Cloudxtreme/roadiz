<?php
/*
 * Copyright © 2015, Ambroise Maupate and Julien Blanchet
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
 * @file DatabaseController.php
 * @author Ambroise Maupate
 */

namespace Themes\Install\Controllers;

use Themes\Install\InstallApp;
use RZ\Roadiz\Console\Tools\Configuration;
use RZ\Roadiz\Console\Tools\Fixtures;
use RZ\Roadiz\Console\Tools\Requirements;

use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\CMS\Controllers\AppController;
use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\CMS\Forms\SeparatorType;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

/**
 * DatabaseController
 */
class DatabaseController extends InstallApp
{
    /**
     * Install database screen.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function databaseAction(Request $request)
    {
        $config = new Configuration();
        $databaseForm = $this->buildDatabaseForm($request, $config);

        if ($databaseForm !== null) {
            $databaseForm->handleRequest();

            if ($databaseForm->isValid()) {
                try {
                    $config->testDoctrineConnexion($databaseForm->getData());


                    $tempConf = $config->getConfiguration();
                    foreach ($databaseForm->getData() as $key => $value) {
                        $tempConf['doctrine'][$key] = $value;
                    }
                    $config->setConfiguration($tempConf);


                    /*
                     * Test connexion
                     */
                    try {
                        $fixtures = new Fixtures();
                        $fixtures->createFolders();

                        $config->writeConfiguration();

                        /*
                         * Force redirect to avoid resending form when refreshing page
                         */
                        $response = new RedirectResponse(
                            $this->getService('urlGenerator')->generate(
                                'installDatabaseSchemaPage'
                            )
                        );
                        $response->prepare($request);

                        return $response->send();
                    } catch (\PDOException $e) {
                        $message = "";
                        if (strstr($e->getMessage(), 'SQLSTATE[')) {
                            preg_match('/SQLSTATE\[(\w+)\] \[(\w+)\] (.*)/', $e->getMessage(), $matches);
                            $message = $matches[3];
                        } else {
                            $message = $e->getMessage();
                        }
                        $this->assignation['error'] = true;
                        $this->assignation['errorMessage'] = ucfirst($message);
                    } catch (\Exception $e) {
                        $this->assignation['error'] = true;
                        $this->assignation['errorMessage'] = $e->getMessage() . PHP_EOL . $e->getTraceAsString();
                    }

                } catch (\Exception $e) {
                    $this->assignation['error'] = true;
                    $this->assignation['errorMessage'] = $e->getMessage();
                }
            }
            $this->assignation['databaseForm'] = $databaseForm->createView();
        }

        return new Response(
            $this->getTwig()->render('steps/database.html.twig', $this->assignation),
            Response::HTTP_OK,
            array('content-type' => 'text/html')
        );
    }

    /**
     * Perform database schema migration.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function databaseSchemaAction(Request $request)
    {
        /*
         * Test connexion
         */
        if (null === $this->getService('em')) {
            $this->assignation['error'] = true;
            $this->assignation['errorMessage'] = $c['session']->getFlashBag()->all();

        } else {
            try {
                \RZ\Roadiz\Console\SchemaCommand::createSchema();
                \RZ\Roadiz\Console\CacheCommand::clearDoctrine();

                /*
                 * Force redirect to install fixtures
                 */
                $response = new RedirectResponse(
                    $this->getService('urlGenerator')->generate(
                        'installDatabaseFixturesPage'
                    )
                );
                $response->prepare($request);

                return $response->send();

            } catch (\PDOException $e) {
                $message = "";
                if (strstr($e->getMessage(), 'SQLSTATE[')) {
                    preg_match('/SQLSTATE\[(\w+)\] \[(\w+)\] (.*)/', $e->getMessage(), $matches);
                    $message = $matches[3];
                } else {
                    $message = $e->getMessage();
                }
                $this->assignation['error'] = true;
                $this->assignation['errorMessage'] = ucfirst($message);
            } catch (\Exception $e) {
                $this->assignation['error'] = true;
                $this->assignation['errorMessage'] = $e->getMessage() . PHP_EOL . $e->getTraceAsString();
            }
        }


        return new Response(
            $this->getTwig()->render('steps/databaseError.html.twig', $this->assignation),
            Response::HTTP_OK,
            array('content-type' => 'text/html')
        );
    }

    /**
     * Perform database fixtures importation.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function databaseFixturesAction(Request $request)
    {
        $fixtures = new Fixtures();
        $fixtures->installFixtures();

        /*
         * files to import
         */
        $installData = json_decode(file_get_contents(ROADIZ_ROOT . "/themes/Install/config.json"), true);
        $this->assignation['imports'] = $installData['importFiles'];


        return new Response(
            $this->getTwig()->render('steps/databaseFixtures.html.twig', $this->assignation),
            Response::HTTP_OK,
            array('content-type' => 'text/html')
        );
    }

    /**
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function updateSchemaAction(Request $request)
    {
        \RZ\Roadiz\Console\SchemaCommand::updateSchema();
        return new Response(
            json_encode(array('status' => true)),
            Response::HTTP_OK,
            array('content-type' => 'application/javascript')
        );
    }

    /**
     * Build forms
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param Themes\Install\Controllers\Configuration $conf
     *
     * @return Symfony\Component\Form\Forms
     */
    protected function buildDatabaseForm(Request $request, Configuration $conf)
    {
        if (isset($conf->getConfiguration()['doctrine'])) {
            $defaults = $conf->getConfiguration()['doctrine'];
        } else {
            $defaults = array();
        }

        $builder = $this->getService('formFactory')
            ->createBuilder('form', $defaults)
            ->add('driver', 'choice', array(
                'choices' => array(
                    'pdo_mysql'=>'pdo_mysql',
                    'pdo_pgsql'=>'pdo_pgsql',
                    'pdo_sqlite' => 'pdo_sqlite',
                    'oci8' => 'oci8',
                ),
                'label' => $this->getTranslator()->trans('driver'),
                'constraints' => array(
                    new NotBlank()
                ),
                'attr' => array(
                    "id" => "choice"
                )
            ))
            ->add('host', 'text', array(
                "required"=>false,
                'label' => $this->getTranslator()->trans('host'),
                'attr'=>array(
                    "autocomplete"=>"off",
                    'id' => "host"
                )
            ))
            ->add('port', 'integer', array(
                "required"=>false,
                'label' => $this->getTranslator()->trans('port'),
                'attr'=>array(
                    "autocomplete"=>"off",
                    'id' => "port"
                )
            ))
            ->add('unix_socket', 'text', array(
                "required"=>false,
                'label' => $this->getTranslator()->trans('unix_socket'),
                'attr'=>array(
                    "autocomplete"=>"off",
                    'id' => "unix_socket"
                )
            ))
            ->add('path', 'text', array(
                "required"=>false,
                'label' => $this->getTranslator()->trans('path'),
                'attr'=>array(
                    "autocomplete"=>"off",
                    'id' => "path"
                )
            ))
            ->add('user', 'text', array(
                'attr'=>array(
                    "autocomplete"=>"off",
                    'id' => "user"
                ),
                'label' => $this->getTranslator()->trans('username'),
                'constraints' => array(
                    new NotBlank()
                )
            ))
            ->add('password', 'password', array(
                "required"=>false,
                'label' => $this->getTranslator()->trans('password'),
                'attr'=>array(
                    "autocomplete"=>"off",
                    'id'=>'password'
                )
            ))
            ->add('dbname', 'text', array(
                "required"=>false,
                'label' => $this->getTranslator()->trans('dbname'),
                'attr'=>array(
                    "autocomplete"=>"off",
                    'id'=>'dbname'
                )
            ));

        return $builder->getForm();
    }
}