<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file GroupsUtilsController.php
 * @copyright REZO ZERO 2014
 * @author Thomas Aufresne
 */

namespace Themes\Rozier\Controllers;

use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Core\Entities\Setting;
use RZ\Roadiz\Core\Entities\SettingGroup;
use Doctrine\Common\Collections\ArrayCollection;
use RZ\Roadiz\Core\Serializers\SettingJsonSerializer;
use RZ\Roadiz\Core\Serializers\SettingCollectionJsonSerializer;
use Themes\Rozier\RozierApp;

use RZ\Roadiz\Core\Exceptions\EntityAlreadyExistsException;

use RZ\Roadiz\CMS\Importers\SettingsImporter;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\File\UploadedFile;

use \Symfony\Component\Form\Form;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

/**
 * {@inheritdoc}
 */
class SettingsUtilsController extends RozierApp
{
    /**
     * Export all settings in a Json file (.rzt).
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function exportAllAction(Request $request)
    {
        $this->validateAccessForRole('ROLE_ACCESS_SETTINGS');
        // if (!($this->getSecurityContext()->isGranted('ROLE_ACCESS_SETTINGS')
        //     || $this->getSecurityContext()->isGranted('ROLE_SUPERADMIN')))
        //     return $this->throw404();

        $groups = $this->getService('em')
                  ->getRepository('RZ\Roadiz\Core\Entities\SettingGroup')
                  ->findAll();
        $lonelySettings = $this->getService('em')
                          ->getRepository('RZ\Roadiz\Core\Entities\Setting')
                          ->findBy(array('settingGroup' => null));
        //\Doctrine\Common\Util\Debug::dump($lonelySettings);
        $tmpGroup = new SettingGroup();
        $tmpGroup->setName('__default__');
        $tmpGroup->addSettings($lonelySettings);
        $groups[] = $tmpGroup;
        $data = SettingCollectionJsonSerializer::serialize($groups);

        $response =  new Response(
            $data,
            Response::HTTP_OK,
            array()
        );

        $response->headers->set(
            'Content-Disposition',
            $response->headers->makeDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                'setting-all-' . date("YmdHis")  . '.rzt'
            )
        ); // Rezo-Zero Type

        $response->prepare($request);

        //echo('toto');

        return $response;
    }

    /**
     * Import a Json file (.rzt) containing setting and setting group.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function importJsonFileAction(Request $request)
    {
        $this->validateAccessForRole('ROLE_ACCESS_SETTINGS');
        // if (!($this->getSecurityContext()->isGranted('ROLE_ACCESS_SETTINGS')
        //     || $this->getSecurityContext()->isGranted('ROLE_SUPERADMIN')))
        //     return $this->throw404();

        $form = $this->buildImportJsonFileForm();

        $form->handleRequest();

        if ($form->isValid() &&
            !empty($form['setting_file'])) {

            $file = $form['setting_file']->getData();

            if (UPLOAD_ERR_OK == $file['error']) {

                $serializedData = file_get_contents($file['tmp_name']);

                if (null !== json_decode($serializedData)) {
                    if (SettingsImporter::importJsonFile($serializedData)) {
                        $msg = $this->getTranslator()->trans('setting.imported');
                        $request->getSession()->getFlashBag()->add('confirm', $msg);
                        $this->getService('logger')->info($msg);

                        $this->getService('em')->flush();

                        // redirect even if its null
                        $response = new RedirectResponse(
                            $this->getService('urlGenerator')->generate(
                                'settingsHomePage'
                            )
                        );
                        $response->prepare($request);
                        return $response->send();
                    } else {
                        $msg = $this->getTranslator()->trans('file.format.not_valid');
                        $request->getSession()->getFlashBag()->add('error', $msg);
                        $this->getService('logger')->error($msg);

                        // redirect even if its null
                        $response = new RedirectResponse(
                            $this->getService('urlGenerator')->generate(
                                'settingsImportPage'
                            )
                        );
                        $response->prepare($request);

                        return $response->send();
                    }
                } else {
                    $msg = $this->getTranslator()->trans('file.format.not_valid');
                    $request->getSession()->getFlashBag()->add('error', $msg);
                    $this->getService('logger')->error($msg);

                    // redirect even if its null
                    $response = new RedirectResponse(
                        $this->getService('urlGenerator')->generate(
                            'settingsImportPage'
                        )
                    );
                    $response->prepare($request);

                    return $response->send();
                }
            } else {
                $msg = $this->getTranslator()->trans('file.not_uploaded');
                $request->getSession()->getFlashBag()->add('error', $msg);
                $this->getService('logger')->error($msg);
            }
        }

        $this->assignation['form'] = $form->createView();

        return new Response(
            $this->getTwig()->render('settings/import.html.twig', $this->assignation),
            Response::HTTP_OK,
            array('content-type' => 'text/html')
        );
    }

    /**
     * @return \Symfony\Component\Form\Form
     */
    private function buildImportJsonFileForm()
    {
        $builder = $this->getService('formFactory')
            ->createBuilder('form')
            ->add('setting_file', 'file', array(
                'label' => $this->getTranslator()->trans('settingFile')
            ));

        return $builder->getForm();
    }
}
