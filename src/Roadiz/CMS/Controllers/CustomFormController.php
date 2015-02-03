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
 * @file CustomFormController.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\CMS\Controllers;

use Doctrine\ORM\EntityManager;
use RZ\Roadiz\CMS\Forms\CustomFormsType;
use RZ\Roadiz\Core\Bags\SettingsBag;
use RZ\Roadiz\Core\Entities\CustomForm;
use RZ\Roadiz\Core\Entities\CustomFormAnswer;
use RZ\Roadiz\Core\Entities\CustomFormFieldAttribute;
use RZ\Roadiz\Core\Exceptions\EntityAlreadyExistsException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Loader\YamlFileLoader;
use \InlineStyle\InlineStyle;

class CustomFormController extends AppController
{
    public static $themeDir = 'Rozier';

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

    public function addAction(Request $request, $customFormId)
    {
        $customForm = $this->getService('em')
                           ->find("RZ\Roadiz\Core\Entities\CustomForm", $customFormId);

        if (null !== $customForm) {
            $closeDate = $customForm->getCloseDate();
            $nowDate = new \DateTime();

            if ($closeDate >= $nowDate) {
                $this->assignation['customForm'] = $customForm;
                $this->assignation['fields'] = $customForm->getFields();

                /*
                 * form
                 */
                $form = $this->buildForm($request, $customForm);
                $form->handleRequest();
                if ($form->isValid()) {
                    try {
                        $data = $form->getData();
                        $data["ip"] = $request->getClientIp();

                        /*
                         * add custom form answer
                         */
                        $this->assignation["fields"] = static::addCustomFormAnswer($data, $customForm, $this->getService('em'));

                        $msg = $this->getTranslator()->trans('customForm.%name%.send', ['%name%' => $customForm->getDisplayName()]);
                        $request->getSession()->getFlashBag()->add('confirm', $msg);
                        $this->getService('logger')->info($msg);

                        $this->assignation['title'] = $this->getTranslator()->trans(
                            'new.answer.form.%site%',
                            ['%site%' => $customForm->getDisplayName()]
                        );

                        $this->assignation['mailContact'] = SettingsBag::get('email_sender');

                        /*
                         * Send answer notification
                         */
                        static::sendAnswer(
                            $this->assignation,
                            $customForm->getEmail(),
                            $this->getService('twig.environment'),
                            $this->getService('mailer')
                        );

                        /*
                         * Redirect to update schema page
                         */
                        $response = new RedirectResponse(
                            $this->getService('urlGenerator')->generate(
                                'customFormSentAction',
                                ["customFormId" => $customFormId]
                            )
                        );

                    } catch (EntityAlreadyExistsException $e) {
                        $request->getSession()->getFlashBag()->add('error', $e->getMessage());
                        $this->getService('logger')->warning($e->getMessage());
                        $response = new RedirectResponse(
                            $this->getService('urlGenerator')->generate(
                                'customFormSendAction',
                                ["customFormId" => $customFormId]
                            )
                        );
                    }
                    $response->prepare($request);

                    return $response->send();
                }

                $this->assignation['form'] = $form->createView();

                return new Response(
                    $this->getTwig()->render('forms/customForm.html.twig', $this->assignation),
                    Response::HTTP_OK,
                    ['content-type' => 'text/html']
                );
            }
        }

        return $this->throw404();
    }

    public function sentAction(Request $request, $customFormId)
    {
        $customForm = $this->getService('em')
                           ->find("RZ\Roadiz\Core\Entities\CustomForm", $customFormId);

        if (null !== $customForm) {
            $this->assignation['customForm'] = $customForm;

            return new Response(
                $this->getTwig()->render('forms/customFormSent.html.twig', $this->assignation),
                Response::HTTP_OK,
                ['content-type' => 'text/html']
            );
        }

        return $this->throw404();
    }

    /**
     * Send an answer form by Email.
     *
     * @param  array             $assignation
     * @param  string            $receiver
     * @param  \Twig_Environment $twigEnv
     * @param  \Swift_Mailer     $mailer
     *
     * @return boolean
     */
    public static function sendAnswer($assignation, $receiver, \Twig_Environment $twigEnv, \Swift_Mailer $mailer)
    {
        $emailBody = $twigEnv->render('forms/answerForm.html.twig', $assignation);

        /*
         * inline CSS
         */
        $htmldoc = new InlineStyle($emailBody);
        $htmldoc->applyStylesheet(file_get_contents(
            ROADIZ_ROOT . "/src/Roadiz/CMS/Resources/css/transactionalStyles.css"
        ));

        if (empty($receiver)) {
            $receiver = SettingsBag::get('email_sender');
        }
        // Create the message}
        $message = \Swift_Message::newInstance();
        // Give the message a subject
        $message->setSubject($assignation['title']);
        // Set the From address with an associative array
        $message->setFrom([SettingsBag::get('email_sender')]);
        // Set the To addresses with an associative array
        $message->setTo([$receiver]);
        // Give it a body
        $message->setBody($htmldoc->getHTML(), 'text/html');

        // Send the message
        return $mailer->send($message);
    }

    /**
     * Add a custom form answer into database.
     *
     * @param array $data Data array from POST form
     * @param RZ\Roadiz\Core\Entities\CustomForm $customForm
     * @param Doctrine\ORM\EntityManager $em
     *
     * @return array $fieldsData
     */
    public static function addCustomFormAnswer($data, CustomForm $customForm, EntityManager $em)
    {
        $now = new \DateTime('NOW');
        $answer = new CustomFormAnswer();
        $answer->setIp($data["ip"]);
        $answer->setSubmittedAt($now);
        $answer->setCustomForm($customForm);

        $fieldsData = [
            ["name" => "ip.address", "value" => $data["ip"]],
            ["name" => "submittedAt", "value" => $now]
        ];

        $em->persist($answer);

        foreach ($customForm->getFields() as $field) {
            $fieldAttr = new CustomFormFieldAttribute();
            $fieldAttr->setCustomFormAnswer($answer);
            $fieldAttr->setCustomFormField($field);

            if ($data[$field->getName()] instanceof \DateTime) {
                $strDate = $data[$field->getName()]->format('Y-m-d H:i:s');

                $fieldAttr->setValue($strDate);
                $fieldsData[] = ["name" => $field->getLabel(), "value" => $strDate];

            } else if (is_array($data[$field->getName()])) {
                $values = [];

                foreach ($data[$field->getName()] as $value) {
                    $choices = explode(',', $field->getDefaultValues());
                    $values[] = $choices[$value];
                }

                $val = implode(',', $values);
                $fieldAttr->setValue(strip_tags($val));
                $fieldsData[] = ["name" => $field->getLabel(), "value" => $val];

            } else {
                $fieldAttr->setValue(strip_tags($data[$field->getName()]));
                $fieldsData[] = ["name" => $field->getLabel(), "value" => $data[$field->getName()]];
            }
            $em->persist($fieldAttr);
        }

        $em->flush();

        return $fieldsData;
    }

    /**
     * @param RZ\Roadiz\Core\Entities\CustomForm $customForm
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildForm(Request $request, CustomForm $customForm)
    {
        $defaults = $request->query->all();
        $form = $this->getService('formFactory')
                     ->create(new CustomFormsType($customForm), $defaults);

        return $form;
    }
}
