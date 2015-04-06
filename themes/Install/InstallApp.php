<?php
/*
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
 * @file InstallApp.php
 * @author Ambroise Maupate
 */

namespace Themes\Install;

use RZ\Roadiz\CMS\Controllers\AppController;
use RZ\Roadiz\CMS\Forms\SeparatorType;
use RZ\Roadiz\Console\Tools\Fixtures;
use RZ\Roadiz\Console\Tools\Requirements;
use RZ\Roadiz\Console\Tools\YamlConfiguration;
use RZ\Roadiz\Core\Bags\SettingsBag;
use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Core\Kernel;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

/**
 * Installation application
 */
class InstallApp extends AppController
{
    protected static $themeName = 'Install theme';
    protected static $themeAuthor = 'Ambroise Maupate';
    protected static $themeCopyright = 'REZO ZERO';
    protected static $themeDir = 'Install';
    protected static $backendTheme = false;

    /**
     * @return array $assignation
     */
    public function prepareBaseAssignation()
    {
        $this->assignation = [
            'request' => $this->kernel->getRequest(),
            'head' => [
                'ajax' => $this->kernel->getRequest()->isXmlHttpRequest(),
                'cmsVersion' => Kernel::CMS_VERSION,
                'cmsVersionNumber' => Kernel::$cmsVersion,
                'cmsBuild' => Kernel::$cmsBuild,
                'devMode' => false,
                'baseUrl' => $this->kernel->getResolvedBaseUrl(),
                'filesUrl' => $this->kernel
                                   ->getRequest()
                                   ->getBaseUrl() . '/' . Document::getFilesFolderName(),
                'resourcesUrl' => $this->getStaticResourcesUrl(),
                'ajaxToken' => $this->getService('csrfProvider')
                                    ->generateCsrfToken(static::AJAX_TOKEN_INTENTION),
                'fontToken' => $this->getService('csrfProvider')
                                    ->generateCsrfToken(static::FONT_TOKEN_INTENTION),
            ],
            'session' => [
                'id' => $this->kernel->getRequest()->getSession()->getId(),
            ],
        ];

        $this->assignation['head']['grunt'] = include dirname(__FILE__) . '/static/public/config/assets.config.php';

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function initializeTranslator()
    {
        $this->getKernel()->getRequest()->setLocale(
            $this->getService('session')->get('_locale', 'en')
        );

        return parent::initializeTranslator();
    }

    /**
     * Welcome screen.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        $form = $this->buildLanguageForm($request);
        $form->handleRequest();

        if ($form->isValid()) {
            $locale = $form->getData()['language'];
            $request->setLocale($locale);
            $this->getService('session')->set('_locale', $locale);
            /*
             * Force redirect to avoid resending form when refreshing page
             */
            $response = new RedirectResponse(
                $this->getService('urlGenerator')->generate(
                    'installHomePage'
                )
            );
            $response->prepare($request);
            return $response->send();
        }

        $this->assignation['form'] = $form->createView();

        return $this->render('steps/hello.html.twig', $this->assignation);
    }

    /**
     * Welcome screen redirect.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function redirectIndexAction(Request $request)
    {
        $response = new RedirectResponse(
            $this->getService('urlGenerator')->generate(
                'installHomePage'
            )
        );

        $response->prepare($request);

        return $response->send();
    }

    /**
     * Check requirement screen.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function requirementsAction(Request $request)
    {
        $requ = new Requirements();
        $this->assignation['requirements'] = $requ->getRequirements();
        $this->assignation['totalSuccess'] = $requ->isTotalSuccess();
        return $this->render('steps/requirements.html.twig', $this->assignation);
    }

    /**
     * User creation screen.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function userAction(Request $request)
    {
        $userForm = $this->buildUserForm($request);

        if ($userForm !== null) {
            $userForm->handleRequest();

            if ($userForm->isValid()) {
                /*
                 * Create user
                 */
                try {
                    $fixtures = new Fixtures();
                    $fixtures->createDefaultUser($userForm->getData());
                    /*
                     * Force redirect to avoid resending form when refreshing page
                     */
                    $user = $this->getService('em')
                                 ->getRepository('RZ\Roadiz\Core\Entities\User')
                                 ->findOneBy(['username' => $userForm->getData()['username']]);

                    $response = new RedirectResponse(
                        $this->getService('urlGenerator')->generate(
                            'installUserSummaryPage',
                            ["userId" => $user->getId()]
                        )
                    );
                    $response->prepare($request);

                    return $response->send();
                } catch (\Exception $e) {
                    $this->assignation['error'] = true;
                    $this->assignation['errorMessage'] = $e->getMessage();
                }

            }
            $this->assignation['userForm'] = $userForm->createView();
        }

        return $this->render('steps/user.html.twig', $this->assignation);
    }

    /**
     * User information screen.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int                                      $userId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function userSummaryAction(Request $request, $userId)
    {
        $user = $this->getService('em')->find('RZ\Roadiz\Core\Entities\User', $userId);
        $this->assignation['name'] = $user->getUsername();
        $this->assignation['email'] = $user->getEmail();
        return $this->render('steps/userSummary.html.twig', $this->assignation);
    }

    /**
     * Install success screen.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function doneAction(Request $request)
    {
        $doneForm = $this->buildDoneForm($request);

        if ($doneForm !== null) {
            $doneForm->handleRequest();

            if ($doneForm->isValid() &&
                $doneForm->getData()['action'] == 'quit_install') {
                /*
                 * Save informations
                 */
                try {
                    $config = new YamlConfiguration();
                    if (false === $config->load()) {
                        $config->setConfiguration($config->getDefaultConfiguration());
                    }
                    $configuration = $config->getConfiguration();
                    $configuration['install'] = false;
                    $config->setConfiguration($configuration);

                    $config->writeConfiguration();

                    \RZ\Roadiz\Console\CacheCommand::clearDoctrine();
                    \RZ\Roadiz\Console\CacheCommand::clearTranslations();

                    /*
                     * Close Session for security and temp translation
                     */
                    $this->getService('session')->invalidate();

                    /*
                     * Force redirect to avoid resending form when refreshing page
                     */
                    $response = new RedirectResponse(
                        $this->getService('urlGenerator')->generate(
                            'installHomePage'
                        )
                    );
                    $response->prepare($request);

                    return $response->send();
                } catch (\Exception $e) {
                    $this->assignation['error'] = true;
                    $this->assignation['errorMessage'] = $e->getMessage() . PHP_EOL . $e->getTraceAsString();
                }

            }
            $this->assignation['doneForm'] = $doneForm->createView();
        }

        return $this->render('steps/done.html.twig', $this->assignation);
    }

    /**
     * Build forms.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\Form\Forms
     */
    protected function buildLanguageForm(Request $request)
    {
        $builder = $this->getService('formFactory')
                        ->createBuilder('form')
                        ->add('language', 'choice', [
                            'choices' => [
                                'en' => 'English',
                                'fr' => 'Français',
                                'ru' => 'Русский язык'
                            ],
                            'constraints' => [
                                new NotBlank(),
                            ],
                            'label' => 'choose.a.language',
                            'attr' => [
                                "id" => "language",
                            ],
                            'data' => $request->getLocale(),
                        ]);

        return $builder->getForm();
    }

    /**
     * Build forms
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\Form\Forms
     */
    protected function buildUserForm(Request $request)
    {
        $builder = $this->getService('formFactory')
                        ->createBuilder('form')
                        ->add('username', 'text', [
                            'required' => true,
                            'label' => $this->getTranslator()->trans('username'),
                            'constraints' => [
                                new NotBlank(),
                            ],
                        ])
                        ->add('email', 'email', [
                            'required' => true,
                            'label' => $this->getTranslator()->trans('email'),
                            'constraints' => [
                                new NotBlank(),
                            ],
                        ])
                        ->add('password', 'repeated', [
                            'type' => 'password',
                            'invalid_message' => 'password.must_match',
                            'first_options' => ['label' => 'password'],
                            'second_options' => ['label' => 'password.verify'],
                            'required' => true,
                            'constraints' => [
                                new NotBlank(),
                            ],
                        ]);

        return $builder->getForm();
    }

    /**
     * Build form for theme and site informations.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\Form\Forms
     */
    protected function buildInformationsForm(Request $request)
    {
        $siteName = SettingsBag::get('site_name');
        $metaDescription = SettingsBag::get('seo_description');
        $emailSender = SettingsBag::get('email_sender');
        $emailSenderName = SettingsBag::get('email_sender_name');
        $timeZone = $this->getService('config')['timezone'];

        $timeZoneList = include dirname(__FILE__) . '/Resources/import/timezones.php';

        $defaults = [
            'site_name' => $siteName != '' ? $siteName : "My website",
            'seo_description' => $metaDescription != '' ? $metaDescription : "My website is beautiful!",
            'email_sender' => $emailSender != '' ? $emailSender : "",
            'email_sender_name' => $emailSenderName != '' ? $emailSenderName : "",
            'install_frontend' => true,
            'timezone' => $timeZone != '' ? $timeZone : "Europe/Paris",
        ];
        $builder = $this->getService('formFactory')
                        ->createBuilder('form', $defaults)
                        ->add('site_name', 'text', [
                            'required' => true,
                            'label' => $this->getTranslator()->trans('site_name'),
                            'constraints' => [
                                new NotBlank(),
                            ],
                        ])
                        ->add('email_sender', 'email', [
                            'required' => true,
                            'label' => $this->getTranslator()->trans('email_sender'),
                            'constraints' => [
                                new NotBlank(),
                            ],
                        ])
                        ->add('email_sender_name', 'text', [
                            'required' => true,
                            'label' => $this->getTranslator()->trans('email_sender_name'),
                            'constraints' => [
                                new NotBlank(),
                            ],
                        ])
                        ->add('seo_description', 'text', [
                            'required' => false,
                            'label' => $this->getTranslator()->trans('meta_description'),
                        ])
                        ->add('timezone', 'choice', [
                            'choices' => $timeZoneList,
                            'label' => $this->getTranslator()->trans('timezone'),
                            'required' => true,
                        ]);

        $themesType = new \RZ\Roadiz\CMS\Forms\ThemesType();

        if ($themesType->getSize() > 0) {
            $builder->add('separator_1', new SeparatorType(), [
                        'label' => $this->getTranslator()->trans('themes.frontend.description'),
                    ])
                    ->add('install_theme', 'checkbox', [
                        'required' => false,
                        'label' => $this->getTranslator()->trans('install_theme'),
                    ])
                    ->add(
                        'className',
                        $themesType,
                        [
                            'label' => $this->getTranslator()->trans('theme.selector'),
                            'required' => true,
                            'constraints' => [
                                new \Symfony\Component\Validator\Constraints\NotNull(),
                                new \Symfony\Component\Validator\Constraints\Type('string'),
                            ],
                        ]
                    );
        }

        return $builder->getForm();
    }

    /**
     * Build forms
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\Form\Forms
     */
    protected function buildDoneForm(Request $request)
    {
        $builder = $this->getService('formFactory')
                        ->createBuilder('form')
                        ->add('action', 'hidden', [
                            'data' => 'quit_install',
                        ]);

        return $builder->getForm();
    }
}
