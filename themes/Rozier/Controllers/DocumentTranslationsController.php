<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file DocumentsController.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace Themes\Rozier\Controllers;

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\Document;
use RZ\Renzo\Core\Entities\DocumentTranslation;
use RZ\Renzo\Core\Entities\Translation;
use RZ\Renzo\Core\ListManagers\EntityListManager;
use RZ\Renzo\Core\Utils\SplashbasePictureFinder;
use Themes\Rozier\RozierApp;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

/**
 * {@inheritdoc}
 */
class DocumentTranslationsController extends RozierApp
{
    /**
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int                                      $documentId
     * @param int                                      $translationId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Request $request, $documentId, $translationId = null)
    {
        $this->validateAccessForRole('ROLE_ACCESS_DOCUMENTS');

        if (null === $translationId) {
            $translation = $this->getService('em')
                    ->getRepository('RZ\Renzo\Core\Entities\Translation')
                    ->findDefault();

            $translationId = $translation->getId();
        } else {
            $translation = $this->getService('em')
                    ->find('RZ\Renzo\Core\Entities\Translation', (int) $translationId);
        }

        $this->assignation['available_translations'] = $this->getService('em')
                                                            ->getRepository('RZ\Renzo\Core\Entities\Translation')
                                                            ->findAll();

        $document = $this->getService('em')
            ->find('RZ\Renzo\Core\Entities\Document', (int) $documentId);
        $documentTr = $this->getService('em')
            ->getRepository('RZ\Renzo\Core\Entities\DocumentTranslation')
            ->findOneBy(array('document'=>(int) $documentId, 'translation'=>(int) $translationId));

        if ($documentTr === null &&
            $document !== null &&
            $translation !== null) {
            $documentTr = $this->createDocumentTranslation($document, $translation);
        }

        if ($documentTr !== null &&
            $document !== null) {

            $this->assignation['document'] = $document;
            $this->assignation['translation'] = $translation;
            $this->assignation['documentTr'] = $documentTr;

            /*
             * Handle main form
             */
            $form = $this->buildEditForm($documentTr);
            $form->handleRequest();

            if ($form->isValid()) {

                $this->editDocument($form->getData(), $documentTr);
                $msg = $this->getTranslator()->trans('document.translation.%name%.updated', array(
                    '%name%'=>$document->getFilename()
                ));
                $request->getSession()->getFlashBag()->add('confirm', $msg);
                $this->getService('logger')->info($msg);
                /*
                 * Force redirect to avoid resending form when refreshing page
                 */
                $response = new RedirectResponse(
                    $this->getService('urlGenerator')->generate(
                        'documentsMetaPage',
                        array(
                            'documentId' => $document->getId(),
                            'translationId' => $translationId
                        )
                    )
                );
                $response->prepare($request);

                return $response->send();
            }

            $this->assignation['form'] = $form->createView();

            return new Response(
                $this->getTwig()->render('document-translations/edit.html.twig', $this->assignation),
                Response::HTTP_OK,
                array('content-type' => 'text/html')
            );
        } else {
            return $this->throw404();
        }
    }

    /**
     * @param Document    $document    [description]
     * @param Translation $translation [description]
     *
     * @return DocumentTranslation
     */
    protected function createDocumentTranslation(Document $document, Translation $translation)
    {
        $dt = new DocumentTranslation();
        $dt->setDocument($document);
        $dt->setTranslation($translation);

        $this->getService('em')->persist($dt);
        $this->getService('em')->flush();

        return $dt;
    }


    /**
     * Return an deletion form for requested document.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int                                      $documentId
     * @param int                                      $translationId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction(Request $request, $documentId, $translationId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_DOCUMENTS_DELETE');

        $documentTr = $this->getService('em')
            ->getRepository('RZ\Renzo\Core\Entities\DocumentTranslation')
            ->findOneBy(array('document'=>(int) $documentId, 'translation'=>(int) $translationId));
        $document = $this->getService('em')
            ->find('RZ\Renzo\Core\Entities\Document', (int) $documentId);

        if ($documentTr !== null &&
            $document !== null) {

            $this->assignation['documentTr'] = $documentTr;
            $this->assignation['document'] = $document;
            $form = $this->buildDeleteForm($documentTr);
            $form->handleRequest();

            if ($form->isValid() &&
                $form->getData()['documentId'] == $documentTr->getId()) {

                try {
                    $this->getService('em')->remove($documentTr);
                    $this->getService('em')->flush();

                    $msg = $this->getTranslator()->trans('document.translation.%name%.deleted', array('%name%'=>$document->getFilename()));
                    $request->getSession()->getFlashBag()->add('confirm', $msg);
                    $this->getService('logger')->info($msg);

                } catch (\Exception $e) {

                    $msg = $this->getTranslator()->trans('document.translation.%name%.cannot_delete', array('%name%'=>$document->getFilename()));
                    $request->getSession()->getFlashBag()->add('error', $msg);
                    $this->getService('logger')->warning($msg);
                }
                /*
                 * Force redirect to avoid resending form when refreshing page
                 */
                $response = new RedirectResponse(
                    $this->getService('urlGenerator')->generate(
                        'documentsEditPage',
                        array('documentId' => $document->getId())
                    )
                );
                $response->prepare($request);

                return $response->send();
            }

            $this->assignation['form'] = $form->createView();

            return new Response(
                $this->getTwig()->render('document-translations/delete.html.twig', $this->assignation),
                Response::HTTP_OK,
                array('content-type' => 'text/html')
            );
        } else {
            return $this->throw404();
        }
    }

    /**
     * @param RZ\Renzo\Core\Entities\DocumentTranslation $doc
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildDeleteForm(DocumentTranslation $doc)
    {
        $defaults = array(
            'documentTranslationId' => $doc->getId()
        );
        $builder = $this->getService('formFactory')
                    ->createBuilder('form', $defaults)
                    ->add('documentTranslationId', 'hidden', array(
                        'data' => $doc->getId(),
                        'constraints' => array(
                            new NotBlank()
                        )
                    ));

        return $builder->getForm();
    }
    /**
     * @param RZ\Renzo\Core\Entities\DocumentTranslation $document
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildEditForm(DocumentTranslation $document)
    {
        $defaults = array(
            'name' => $document->getName(),
            'description' => $document->getDescription(),
            'copyright' => $document->getCopyright()
        );

        $builder = $this->getService('formFactory')
                    ->createBuilder('form', $defaults)
                    ->add('name', 'text', array(
                        'label' => $this->getTranslator()->trans('name'),
                        'required' => false
                    ))
                    ->add('description', new \RZ\Renzo\CMS\Forms\MarkdownType(), array(
                        'label' => $this->getTranslator()->trans('description'),
                        'required' => false
                    ))
                    ->add('copyright', 'text', array(
                        'label' => $this->getTranslator()->trans('copyright'),
                        'required' => false
                    ));

        return $builder->getForm();
    }


    /**
     * @param array                                      $data
     * @param RZ\Renzo\Core\Entities\DocumentTranslation $document
     */
    private function editDocument($data, DocumentTranslation $document)
    {
        foreach ($data as $key => $value) {
            $setter = 'set'.ucwords($key);
            $document->$setter($value);
        }

        $this->getService('em')->flush();
    }
}
