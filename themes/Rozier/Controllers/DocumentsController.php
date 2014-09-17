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
use RZ\Renzo\Core\Entities\Translation;
use RZ\Renzo\Core\ListManagers\EntityListManager;
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
class DocumentsController extends RozierApp
{
    /**
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        /*
         * Manage get request to filter list
         */
        $listManager = new EntityListManager(
            $request,
            $this->getKernel()->em(),
            'RZ\Renzo\Core\Entities\Document'
        );

        $listManager->handle();

        $this->assignation['filters'] = $listManager->getAssignation();
        $this->assignation['documents'] = $listManager->getEntities();

        $this->assignation['thumbnailFormat'] = array(
            'width' => 100,
            'quality' => 50,
            'crop' => '3x2'
        );

        return new Response(
            $this->getTwig()->render('documents/list.html.twig', $this->assignation),
            Response::HTTP_OK,
            array('content-type' => 'text/html')
        );
    }

    /**
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int                                      $documentId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Request $request, $documentId)
    {
        $document = $this->getKernel()->em()
            ->find('RZ\Renzo\Core\Entities\Document', (int) $documentId);

        if ($document !== null) {

            $this->assignation['document'] = $document;
            $this->assignation['thumbnailFormat'] = array(
                'width' => 500,
                'quality' => 70
            );

            /*
             * Handle main form
             */
            $form = $this->buildEditForm($document);
            $form->handleRequest();

            if ($form->isValid()) {

                $this->editDocument($form->getData(), $document);
                $msg = $this->getTranslator()->trans('document.updated', array(
                    '%name%'=>$document->getFilename()
                ));
                $request->getSession()->getFlashBag()->add('confirm', $msg);
                $this->getLogger()->info($msg);
                /*
                 * Force redirect to avoid resending form when refreshing page
                 */
                $response = new RedirectResponse(
                    $this->getKernel()->getUrlGenerator()->generate(
                        'documentsEditPage',
                        array('documentId' => $document->getId())
                    )
                );
                $response->prepare($request);

                return $response->send();
            }

            $this->assignation['form'] = $form->createView();

            return new Response(
                $this->getTwig()->render('documents/edit.html.twig', $this->assignation),
                Response::HTTP_OK,
                array('content-type' => 'text/html')
            );
        } else {
            return $this->throw404();
        }
    }

    /**
     * Return an deletion form for requested document.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int                                      $documentId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction(Request $request, $documentId)
    {
        $document = $this->getKernel()->em()
            ->find('RZ\Renzo\Core\Entities\Document', (int) $documentId);

        if ($document !== null) {
            $this->assignation['document'] = $document;
            $form = $this->buildDeleteForm($document);
            $form->handleRequest();

            if ($form->isValid() &&
                $form->getData()['documentId'] == $document->getId()) {

                try {
                    $document->getHandler()->removeWithAssets();
                    $msg = $this->getTranslator()->trans('document.deleted', array('%name%'=>$document->getFilename()));
                    $request->getSession()->getFlashBag()->add('confirm', $msg);
                    $this->getLogger()->info($msg);

                } catch (\Exception $e) {

                    $msg = $this->getTranslator()->trans('document.cannot_delete', array('%name%'=>$document->getFilename()));
                    $request->getSession()->getFlashBag()->add('error', $msg);
                    $this->getLogger()->warning($msg);
                }
                /*
                 * Force redirect to avoid resending form when refreshing page
                 */
                $response = new RedirectResponse(
                    $this->getKernel()->getUrlGenerator()->generate('documentsHomePage')
                );
                $response->prepare($request);

                return $response->send();
            }

            $this->assignation['form'] = $form->createView();

            return new Response(
                $this->getTwig()->render('documents/delete.html.twig', $this->assignation),
                Response::HTTP_OK,
                array('content-type' => 'text/html')
            );
        } else {
            return $this->throw404();
        }
    }

    /**
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function uploadAction(Request $request)
    {
        /*
         * Handle main form
         */
        $form = $this->buildUploadForm();
        $form->handleRequest();

        if ($form->isValid()) {

            if (false !== $document = $this->uploadDocument($form)) {

                $msg = $this->getTranslator()->trans('document.uploaded', array(
                    '%name%'=>$document->getFilename()
                ));
                $request->getSession()->getFlashBag()->add('confirm', $msg);
                $this->getLogger()->info($msg);

                $response = new Response();
                $response->setContent(json_encode(array(
                    'success' => true,
                )));
                $response->headers->set('Content-Type', 'application/json');
                $response->setStatusCode(200);
                $response->prepare($request);

                return $response->send();

            } else {
                $msg = $this->getTranslator()->trans('document.cannot_persist');
                $request->getSession()->getFlashBag()->add('error', $msg);
                $this->getLogger()->error($msg);

                $response = new Response();
                $response->setContent(json_encode(array(
                    "error" => $this->getTranslator()->trans('document.cannot_persist')
                )));
                $response->headers->set('Content-Type', 'application/json');
                $response->setStatusCode(400);
                $response->prepare($request);

                return $response->send();
            }
        }
        $this->assignation['form'] = $form->createView();
        $this->assignation['maxUploadSize'] = \Symfony\Component\HttpFoundation\File\UploadedFile::getMaxFilesize()  / 1024 / 1024;

        return new Response(
            $this->getTwig()->render('documents/upload.html.twig', $this->assignation),
            Response::HTTP_OK,
            array('content-type' => 'text/html')
        );
    }

    /**
     * @param RZ\Renzo\Core\Entities\Document $doc
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildDeleteForm(Document $doc)
    {
        $defaults = array(
            'documentId' => $doc->getId()
        );
        $builder = $this->getFormFactory()
                    ->createBuilder('form', $defaults)
                    ->add('documentId', 'hidden', array(
                        'data' => $doc->getId(),
                        'constraints' => array(
                            new NotBlank()
                        )
                    ));

        return $builder->getForm();
    }
    /**
     * @param RZ\Renzo\Core\Entities\Document $document
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildEditForm(Document $document)
    {
        $defaults = array(
            'private' => $document->isPrivate(),
            'name' => $document->getName(),
            'description' => $document->getDescription(),
            'copyright' => $document->getCopyright(),
        );

        $builder = $this->getFormFactory()
                    ->createBuilder('form', $defaults)
                    ->add('name', 'text', array('required' => false))
                    ->add('description', new \RZ\Renzo\CMS\Forms\MarkdownType(), array('required' => false))
                    ->add('copyright', 'text', array('required' => false))
                    ->add('private', 'checkbox', array('required' => false));

        return $builder->getForm();
    }

    /**
     * @return Symfony\Component\Form\Form
     */
    private function buildUploadForm()
    {
        $builder = $this->getFormFactory()
                    ->createBuilder('form')
                    ->add('attachment', 'file');

        return $builder->getForm();
    }

    /**
     * @param array                           $data
     * @param RZ\Renzo\Core\Entities\Document $document
     */
    private function editDocument($data, Document $document)
    {
        foreach ($data as $key => $value) {
            $setter = 'set'.ucwords($key);
            $document->$setter($value);
        }

        $this->getKernel()->em()->flush();
    }

    /**
     * Handle upload form data to create a Document.
     *
     * @param Symfony\Component\Form\Form $data
     *
     * @return boolean
     */
    private function uploadDocument($data)
    {
        if (!empty($data['attachment'])) {

            $file = $data['attachment']->getData();

            $uploadedFile = new \Symfony\Component\HttpFoundation\File\UploadedFile(
                $file['tmp_name'],
                $file['name'],
                $file['type'],
                $file['size'],
                $file['error']
            );

            if ($uploadedFile !== null &&
                $uploadedFile->getError() == UPLOAD_ERR_OK &&
                $uploadedFile->isValid()) {

                try {
                    $document = new Document();
                    $document->setFilename($uploadedFile->getClientOriginalName());
                    $document->setMimeType($uploadedFile->getMimeType());

                    $this->getKernel()->em()->persist($document);
                    $this->getKernel()->em()->flush();

                    $uploadedFile->move(Document::getFilesFolder().'/'.$document->getFolder(), $document->getFilename());

                    return $document;
                } catch (\Exception $e) {

                    return false;
                }
            }
        }

        return false;
    }
}
