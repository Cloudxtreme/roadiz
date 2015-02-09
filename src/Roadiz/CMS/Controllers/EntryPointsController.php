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
 * @file EntryPointsController.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\CMS\Controllers;

use RZ\Roadiz\Core\Bags\SettingsBag;
use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Utils\StringHandler;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Loader\YamlFileLoader;
use \InlineStyle\InlineStyle;

/**
 * Defines entry points for Roadiz.
 */
class EntryPointsController extends AppController
{
    const CONTACT_FORM_TOKEN_INTENTION = 'contact_form';

    protected static $mandatoryContactFields = [
        'email',
        'message',
    ];

    /**
     * Initialize controller with NO twig environment.
     */
    public function __init()
    {
        $this->getTwigLoader()
             ->prepareBaseAssignation();
    }

    /**
     * @return string
     */
    public static function getResourcesFolder()
    {
        return ROADIZ_ROOT . '/src/Roadiz/CMS/Resources';
    }

    /**
     * {@inheritdoc}
     */
    public static function getRoutes()
    {
        $locator = new FileLocator([
            ROADIZ_ROOT . '/src/Roadiz/CMS/Resources',
        ]);

        if (file_exists(ROADIZ_ROOT . '/src/Roadiz/CMS/Resources/entryPointsRoutes.yml')) {
            $loader = new YamlFileLoader($locator);

            return $loader->load('entryPointsRoutes.yml');
        }

        return null;
    }

    /**
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param string                                   $method
     *
     * @return boolean | array  Return true if request is valid, else return error array
     */
    protected function validateRequest(Request $request, $method = 'POST')
    {
        if ($request->getMethod() != $method ||
            !is_array($request->get('form'))) {
            return [
                'statusCode' => Response::HTTP_FORBIDDEN,
                'status' => 'danger',
                'responseText' => 'Wrong request',
            ];
        }
        if (!$this->getService('csrfProvider')
            ->isCsrfTokenValid(static::CONTACT_FORM_TOKEN_INTENTION, $request->get('form')['_token'])) {
            return [
                'statusCode' => Response::HTTP_FORBIDDEN,
                'status' => 'danger',
                'responseText' => 'Bad token',
            ];
        }

        return true;
    }

    /**
     * Handles contact forms requests.
     *
     * File upload are allowed if file size is less than 5MB and mimeType
     * is PDF or image.
     *
     * * application/pdf
     * * application/x-pdf
     * * image/jpeg
     * * image/png
     * * image/gif
     *
     * @param  Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function contactFormAction(Request $request, $_locale = null)
    {
        if (true !== $validation = $this->validateRequest($request)) {
            return new Response(
                json_encode($validation),
                Response::HTTP_FORBIDDEN,
                ['content-type' => 'application/javascript']
            );
        }
        $canSend = true;
        $responseArray = [
            'statusCode' => Response::HTTP_OK,
            'status' => 'success',
            'field_error' => null,
        ];

        foreach (static::$mandatoryContactFields as $mandatoryField) {
            if (empty($request->get('form')[$mandatoryField])) {
                $responseArray['statusCode'] = Response::HTTP_FORBIDDEN;
                $responseArray['status'] = 'danger';
                $responseArray['field_error'] = $mandatoryField;
                $responseArray['message'] = $this->getTranslator()->trans(
                    '%field%.is.mandatory',
                    ['%field%' => ucwords($mandatoryField)]
                );

                $request->getSession()->getFlashBag()->add('error', $responseArray['message']);
                $this->getService('logger')->error($responseArray['message']);

                $canSend = false;
            }
        }

        if (false === filter_var($request->get('form')['email'], FILTER_VALIDATE_EMAIL)) {
            $responseArray['statusCode'] = Response::HTTP_FORBIDDEN;
            $responseArray['status'] = 'danger';
            $responseArray['field_error'] = 'email';
            $responseArray['message'] = $this->getTranslator()->trans(
                'email.not.valid'
            );

            $request->getSession()->getFlashBag()->add('error', $responseArray['message']);
            $this->getService('logger')->error($responseArray['message']);

            $canSend = false;
        }

        $allowedMimeTypes = [
            'application/pdf',
            'application/x-pdf',
            'image/jpeg',
            'image/png',
            'image/gif',
        ];
        $maxFileSize = 1024 * 1024 * 5; // 5MB
        $uploadedFiles = [];
        /*
         * Files values
         */
        foreach ($request->files as $files) {
            foreach ($files as $name => $uploadedFile) {
                if (!$uploadedFile->isValid() ||
                    !in_array($uploadedFile->getMimeType(), $allowedMimeTypes) ||
                    $uploadedFile->getClientSize() > $maxFileSize) {
                    $responseArray['statusCode'] = Response::HTTP_FORBIDDEN;
                    $responseArray['status'] = 'danger';
                    $responseArray['field_error'] = $name;
                    $responseArray['message'] = $this->getTranslator()->trans(
                        'file.not.accepted'
                    );

                    $request->getSession()->getFlashBag()->add('error', $responseArray['message']);
                    $this->getService('logger')->error($responseArray['message']);
                    $canSend = false;
                } else {
                    $uploadedFiles[$name] = $uploadedFile;
                }
            }
        }

        /*
         * if no error, create Email
         */
        if ($canSend) {
            $receiver = SettingsBag::get('email_sender');

            $assignation = [
                'mailContact' => SettingsBag::get('email_sender'),
                'title' => $this->getTranslator()->trans(
                    'new.contact.form.%site%',
                    ['%site%' => SettingsBag::get('site_name')]
                ),
                'email' => $request->get('form')['email'],
                'fields' => [],
            ];

            /*
             * text values
             */
            foreach ($request->get('form') as $key => $value) {
                if ($key[0] == '_') {
                    continue;
                } elseif (!empty($value)) {
                    $assignation['fields'][] = [
                        'name' => strip_tags($key),
                        'value' => (strip_tags($value)),
                    ];
                }
            }

            /*
             * Files values
             */
            foreach ($uploadedFiles as $key => $uploadedFile) {
                $assignation['fields'][] = [
                    'name' => strip_tags($key),
                    'value' => (strip_tags($uploadedFile->getClientOriginalName()) . ' [' . $uploadedFile->guessExtension() . ']'),
                ];
            }

            /*
             *  Date
             */
            $assignation['fields'][] = [
                'name' => $this->getTranslator()->trans('date'),
                'value' => (new \DateTime())->format('Y-m-d H:i:s'),
            ];
            /*
             *  IP
             */
            $assignation['fields'][] = [
                'name' => $this->getTranslator()->trans('ip.address'),
                'value' => $request->getClientIp(),
            ];

            /*
             * Custom receiver
             */
            if (!empty($request->get('form')['_emailReceiver'])) {
                $email = StringHandler::decodeWithSecret(
                    $request->get('form')['_emailReceiver'],
                    $this->getService('config')['security']['secret']
                );
                if (false !== filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $receiver = $email;
                }
            }

            /*
             * Custom subject
             */
            if (!empty($request->get('form')['_emailSubject'])) {
                $subject = StringHandler::decodeWithSecret(
                    $request->get('form')['_emailSubject'],
                    $this->getService('config')['security']['secret']
                );
            } else {
                $subject = null;
            }

            /*
             * Send contact form email
             */
            $this->sendContactForm($assignation, $receiver, $subject, $uploadedFiles);

            $responseArray['message'] = $this->getTranslator()->trans(
                'form.successfully.sent'
            );

            $request->getSession()->getFlashBag()->add('confirm', $responseArray['message']);
            $this->getService('logger')->info($responseArray['message']);
        }

        /*
         * If no AJAX and a redirect URL is present,
         * just redirect.
         */
        if (!empty($request->get('form')['_redirect'])) {
            $response = new RedirectResponse($request->get('form')['_redirect']);
            $response->prepare($request);

            return $response->send();

        } else {
            return new Response(
                json_encode($responseArray),
                Response::HTTP_OK,
                ['content-type' => 'application/javascript']
            );
        }
    }

    /**
     * Generate a form-builder for contact forms.
     *
     * For example in your contact page controller :
     *
     * <pre>
     * use RZ\Roadiz\CMS\Controllers\EntryPointsController;
     *
     * ...
     *
     * $formBuilder = EntryPointsController::getContactFormBuilder(
     *     $request,
     *     true
     * );
     * $formBuilder->add('email', 'email', array(
     *                 'label'=>$this->getTranslator()->trans('your.email')
     *             ))
     *             ->add('name', 'text', array(
     *                 'label'=>$this->getTranslator()->trans('your.name')
     *             ))
     *             ->add('message', 'textarea', array(
     *                 'label'=>$this->getTranslator()->trans('your.message')
     *             ))
     *             ->add('callMeBack', 'checkbox', array(
     *                 'label'=>$this->getTranslator()->trans('call.me.back'),
     *                 'required' => false
     *             ))
     *             ->add('send', 'submit', array(
     *                 'label'=>$this->getTranslator()->trans('send.contact.form')
     *             ));
     * $form = $formBuilder->getForm();
     * $this->assignation['contactForm'] = $form->createView();
     *
     * </pre>
     *
     * Add session messages to your assignations
     *
     * <pre>
     * // Get session messages
     * $this->assignation['session']['messages'] = $this->getService('session')->getFlashBag()->all();
     * </pre>
     *
     * Then in your contact page Twig template
     *
     * <pre>
     * {#
     *  # Display contact errors
     *  #}
     * {% if session.messages|length %}
     *     {% for type, msgs in session.messages %}
     *         {% for msg in msgs %}
     *             <div data-uk-alert class="uk-alert
     *                                       uk-alert-{% if type == "confirm" %}success
     *                                       {% elseif type == "warning" %}warning{% else %}danger{% endif %}">
     *                 <a href="" class="uk-alert-close uk-close"></a>
     *                 <p>{{ msg }}</p>
     *             </div>
     *         {% endfor %}
     *     {% endfor %}
     * {% endif %}
     * {#
     *  # Display contact form
     *  #}
     * {% form_theme contactForm 'forms.html.twig' %}
     * {{ form(contactForm) }}
     * </pre>
     *
     * @param Symfony\Component\HttpFoundation\Request $request             Contact page request
     * @param boolean                                  $redirect            Redirect to contact page after sending?
     * @param string                                   $customRedirectUrl   Redirect to a custom url
     * @param string                                   $customEmailReceiver Send contact form to a custom email (or emails)
     * @param string                                   $customEmailSubject  Customize email subject
     *
     * @return Symfony\Component\Form\FormBuilder
     */
    public static function getContactFormBuilder(
        Request $request,
        $redirect = true,
        $customRedirectUrl = null,
        $customEmailReceiver = null,
        $customEmailSubject = null
    ) {
        $action = Kernel::getService('urlGenerator')
            ->generate('contactFormLocaleAction', [
                '_locale' => $request->getLocale(),
            ]);

        $builder = Kernel::getService('formFactory')
            ->createBuilder('form', null, [
                'csrf_provider' => Kernel::getService('csrfProvider'),
                'intention' => static::CONTACT_FORM_TOKEN_INTENTION,
                'attr' => [
                    'id' => 'contactForm',
                ],
            ])
            ->setMethod('POST')
            ->setAction($action)
            ->add('_action', 'hidden', [
                'data' => 'contact',
            ]);

        if (true === $redirect) {
            if (null !== $customRedirectUrl) {
                $builder->add('_redirect', 'hidden', [
                    'data' => strip_tags($customRedirectUrl),
                ]);
            } else {
                $builder->add('_redirect', 'hidden', [
                    'data' => strip_tags($request->getURI()),
                ]);
            }
        }

        if (null !== $customEmailReceiver) {
            $builder->add('_emailReceiver', 'hidden', [
                'data' => StringHandler::encodeWithSecret($customEmailReceiver, Kernel::getService('config')['security']['secret']),
            ]);
        }

        if (null !== $customEmailSubject) {
            $builder->add('_emailSubject', 'hidden', [
                'data' => StringHandler::encodeWithSecret($customEmailSubject, Kernel::getService('config')['security']['secret']),
            ]);
        }

        return $builder;
    }

    /**
     * Send a contact form by Email.
     *
     * @param array $assignation
     * @param string $receiver
     * @param string|null $subject
     * @param array $files
     *
     * @return boolean
     */
    protected function sendContactForm($assignation, $receiver, $subject = null, $files = null)
    {
        $emailBody = $this->getService('twig.environment')->render('forms/contactForm.html.twig', $assignation);
        /*
         * inline CSS
         */
        $htmldoc = new InlineStyle($emailBody);
        $htmldoc->applyStylesheet(file_get_contents(
            ROADIZ_ROOT . "/src/Roadiz/CMS/Resources/css/transactionalStyles.css"
        ));

        if (null !== $subject) {
            $subject = trim(strip_tags($subject));
        } else {
            $subject = $this->getTranslator()->trans(
                'new.contact.form.%site%',
                ['%site%' => SettingsBag::get('site_name')]
            );
        }

        // Create the message
        $message = \Swift_Message::newInstance()
        // Give the message a subject
        ->setSubject($subject)
        // Set the From address with an associative array
        ->setFrom([$assignation['email']])
        // Set the To addresses with an associative array
        ->setTo([$receiver])
        // Give it a body
        ->setBody($htmldoc->getHTML(), 'text/html');

        /*
         * Attach files
         */
        foreach ($files as $uploadedFile) {
            $attachment = \Swift_Attachment::fromPath($uploadedFile->getRealPath())
                ->setFilename($uploadedFile->getClientOriginalName());
            $message->attach($attachment);
        }

        // Send the message
        return $this->getService('mailer')->send($message);
    }
}
