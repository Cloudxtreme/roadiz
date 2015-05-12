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
 *
 * @file TagsController.php
 * @author Ambroise Maupate
 */
namespace Themes\Rozier\Controllers;

use RZ\Roadiz\Core\Entities\Tag;
use RZ\Roadiz\Core\Entities\TagTranslation;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\Events\FilterTagEvent;
use RZ\Roadiz\Core\Events\TagEvents;
use RZ\Roadiz\Core\Exceptions\EntityAlreadyExistsException;
use RZ\Roadiz\Core\ListManagers\EntityListManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\NotBlank;
use Themes\Rozier\Forms\TagTranslationType;
use Themes\Rozier\Forms\TagType;
use Themes\Rozier\RozierApp;
use Themes\Rozier\Widgets\TagTreeWidget;

/**
 * {@inheritdoc}
 */
class TagsController extends RozierApp
{
    /**
     * List every tags.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        $this->validateAccessForRole('ROLE_ACCESS_TAGS');

        /*
         * Manage get request to filter list
         */
        $listManager = new EntityListManager(
            $request,
            $this->getService('em'),
            'RZ\Roadiz\Core\Entities\Tag'
        );
        $listManager->handle();

        $this->assignation['filters'] = $listManager->getAssignation();
        $this->assignation['tags'] = $listManager->getEntities();

        return $this->render('tags/list.html.twig', $this->assignation);
    }

    /**
     * Return an edition form for current translated tag.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param integer                                  $tagId
     * @param integer | null                           $translationId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function editTranslatedAction(Request $request, $tagId, $translationId = null)
    {
        $this->validateAccessForRole('ROLE_ACCESS_TAGS');

        if (null === $translationId) {
            $translation = $this->getService('em')
                                ->getRepository('RZ\Roadiz\Core\Entities\Translation')
                                ->findDefault();
        } else {
            $translation = $this->getService('em')
                                ->find('RZ\Roadiz\Core\Entities\Translation', (int) $translationId);
        }

        if (null !== $translation) {
            /*
             * Here we need to directly select tagTranslation
             * if not doctrine will grab a cache tag because of TagTreeWidget
             * that is initialized before calling route method.
             */
            $gtag = $this->getService('em')
                         ->find('RZ\Roadiz\Core\Entities\Tag', (int) $tagId);

            $tt = $this->getService('em')
                       ->getRepository('RZ\Roadiz\Core\Entities\TagTranslation')
                       ->findOneBy(['translation' => $translation, 'tag' => $gtag]);

            if (null !== $tt) {
                /*
                 * Tag is already translated
                 */
                $tag = $tt->getTag();
                $this->assignation['tag'] = $tag;
                $this->assignation['translatedTag'] = $tt;
                $this->assignation['translation'] = $translation;
                $this->assignation['available_translations'] = $this->getService('em')
                     ->getRepository('RZ\Roadiz\Core\Entities\Translation')
                     ->findAllAvailable();

                $form = $this->createForm(new TagTranslationType(), $tt, [
                    'em' => $this->getService('em'),
                ]);
                $form->handleRequest();

                if ($form->isValid()) {
                    $this->getService('em')->flush();

                    /*
                     * Dispatch event
                     */
                    $event = new FilterTagEvent($tag);
                    $this->getService('dispatcher')->dispatch(TagEvents::TAG_UPDATED, $event);

                    $msg = $this->getTranslator()->trans('tag.%name%.updated', [
                        '%name%' => $tag->getTranslatedTags()->first()->getName(),
                    ]);
                    $this->publishConfirmMessage($request, $msg);
                    /*
                     * Force redirect to avoid resending form when refreshing page
                     */
                    return $this->redirect($this->generateUrl(
                        'tagsEditTranslatedPage',
                        ['tagId' => $tag->getId(), 'translationId' => $translation->getId()]
                    ));
                }

                $this->assignation['form'] = $form->createView();

            } else {
                /*
                 * If translation does not exist, we created it.
                 */
                $this->getService('em')->refresh($gtag);

                if ($gtag !== null) {
                    $baseTranslation = $gtag->getTranslatedTags()->first();

                    $translatedTag = new TagTranslation($gtag, $translation);

                    if (false !== $baseTranslation) {
                        $translatedTag->setName($baseTranslation->getName());
                    } else {
                        $translatedTag->setName('tag_' . $gtag->getId());
                    }
                    $this->getService('em')->persist($translatedTag);
                    $this->getService('em')->flush();

                    /*
                     * Dispatch event
                     */
                    $event = new FilterTagEvent($gtag);
                    $this->getService('dispatcher')->dispatch(TagEvents::TAG_UPDATED, $event);

                    return $this->redirect($this->generateUrl(
                        'tagsEditTranslatedPage',
                        [
                            'tagId' => $gtag->getId(),
                            'translationId' => $translation->getId(),
                        ]
                    ));

                } else {
                    return $this->throw404();
                }
            }

            return $this->render('tags/edit.html.twig', $this->assignation);
        } else {
            return $this->throw404();
        }
    }

    /**
     * Return an creation form for requested tag.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function addAction(Request $request)
    {
        $this->validateAccessForRole('ROLE_ACCESS_TAGS');

        $tag = new Tag();

        $translation = $this->getService('em')
                            ->getRepository('RZ\Roadiz\Core\Entities\Translation')
                            ->findDefault();

        if ($tag !== null &&
            $translation !== null) {
            $this->assignation['tag'] = $tag;
            $form = $this->createForm(new TagType(), $tag, [
                'em' => $this->getService('em'),
            ]);
            $form->handleRequest();

            if ($form->isValid()) {
                $this->getService('em')->persist($tag);
                $this->getService('em')->flush();

                $translatedTag = new TagTranslation($tag, $translation);
                $this->getService('em')->persist($translatedTag);
                $this->getService('em')->flush();

                /*
                 * Dispatch event
                 */
                $event = new FilterTagEvent($tag);
                $this->getService('dispatcher')->dispatch(TagEvents::TAG_CREATED, $event);

                $msg = $this->getTranslator()->trans('tag.%name%.created', ['%name%' => $tag->getTagName()]);
                $this->publishConfirmMessage($request, $msg);
                /*
                 * Force redirect to avoid resending form when refreshing page
                 */
                return $this->redirect($this->generateUrl('tagsHomePage'));
            }

            $this->assignation['form'] = $form->createView();

            return $this->render('tags/add.html.twig', $this->assignation);
        } else {
            return $this->throw404();
        }
    }

    /**
     * Return a edition form for requested tag settings .
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int                                      $tagId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function editSettingsAction(Request $request, $tagId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_TAGS');

        $translation = $this->getService('em')
                            ->getRepository('RZ\Roadiz\Core\Entities\Translation')
                            ->findDefault();

        $tag = $this->getService('em')
                    ->find('RZ\Roadiz\Core\Entities\Tag', (int) $tagId);

        if ($tag !== null) {
            $form = $this->createForm(new TagType(), $tag, [
                'em' => $this->getService('em'),
                'tagName' => $tag->getTagName(),
            ]);

            $form->handleRequest();

            if ($form->isValid()) {
                $this->getService('em')->flush();
                /*
                 * Dispatch event
                 */
                $event = new FilterTagEvent($tag);
                $this->getService('dispatcher')->dispatch(TagEvents::TAG_UPDATED, $event);

                $msg = $this->getTranslator()->trans('tag.%name%.updated', ['%name%' => $tag->getTagName()]);
                $this->publishConfirmMessage($request, $msg);

                /*
                 * Force redirect to avoid resending form when refreshing page
                 */
                return $this->redirect($this->generateUrl(
                    'tagsSettingsPage',
                    ['tagId' => $tag->getId()]
                ));
            }

            $this->assignation['form'] = $form->createView();
            $this->assignation['tag'] = $tag;
            $this->assignation['translation'] = $translation;

            return $this->render('tags/settings.html.twig', $this->assignation);
        }
    }

    /**
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int                                      $tagId
     * @param int                                      $translationId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function treeAction(Request $request, $tagId, $translationId = null)
    {
        $this->validateAccessForRole('ROLE_ACCESS_TAGS');

        $tag = $this->getService('em')
                    ->find('RZ\Roadiz\Core\Entities\Tag', (int) $tagId);
        $this->getService('em')->refresh($tag);

        $translation = null;
        if (null !== $translationId) {
            $translation = $this->getService('em')
                                ->getRepository('RZ\Roadiz\Core\Entities\Translation')
                                ->findOneBy(['id' => (int) $translationId]);
        } else {
            $translation = $this->getService('em')
                                ->getRepository('RZ\Roadiz\Core\Entities\Translation')
                                ->findDefault();
        }

        if (null !== $tag) {
            $widget = new TagTreeWidget($request, $this, $tag);
            $this->assignation['tag'] = $tag;
            $this->assignation['translation'] = $translation;
            $this->assignation['specificTagTree'] = $widget;
        }

        return $this->render('tags/tree.html.twig', $this->assignation);
    }

    /**
     * Return a deletion form for requested tag.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int                                      $tagId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction(Request $request, $tagId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_TAGS_DELETE');

        $tag = $this->getService('em')
                    ->find('RZ\Roadiz\Core\Entities\Tag', (int) $tagId);

        if ($tag !== null &&
            !$tag->isLocked()) {
            $this->assignation['tag'] = $tag;

            $form = $this->buildDeleteForm($tag);
            $form->handleRequest();

            if ($form->isValid() &&
                $form->getData()['tagId'] == $tag->getId()) {
                /*
                 * Dispatch event
                 */
                $event = new FilterTagEvent($tag);
                $this->getService('dispatcher')->dispatch(TagEvents::TAG_DELETED, $event);

                $this->deleteTag($form->getData(), $tag);

                $msg = $this->getTranslator()->trans('tag.%name%.deleted', ['%name%' => $tag->getTranslatedTags()->first()->getName()]);
                $this->publishConfirmMessage($request, $msg);

                /*
                 * Force redirect to avoid resending form when refreshing page
                 */
                return $this->redirect($this->generateUrl('tagsHomePage'));
            }

            $this->assignation['form'] = $form->createView();

            return $this->render('tags/delete.html.twig', $this->assignation);
        } else {
            return $this->throw404();
        }
    }

    /**
     * Handle tag creation pages.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int                                      $tagId
     * @param int                                      $translationId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function addChildAction(Request $request, $tagId, $translationId = null)
    {
        $this->validateAccessForRole('ROLE_ACCESS_TAGS');

        $translation = $this->getService('em')
                            ->getRepository('RZ\Roadiz\Core\Entities\Translation')
                            ->findDefault();

        if ($translationId !== null) {
            $translation = $this->getService('em')
                                ->find('RZ\Roadiz\Core\Entities\Translation', (int) $translationId);
        }
        $parentTag = $this->getService('em')
                          ->find('RZ\Roadiz\Core\Entities\Tag', (int) $tagId);
        $tag = new Tag();
        $tag->setParent($parentTag);

        if ($translation !== null &&
            $parentTag !== null) {
            $form = $this->createForm(new TagType(), $tag, [
                'em' => $this->getService('em'),
            ]);
            $form->handleRequest();

            if ($form->isValid()) {
                try {
                    $this->getService('em')->persist($tag);
                    $this->getService('em')->flush();

                    $translatedTag = new TagTranslation($tag, $translation);
                    $this->getService('em')->persist($translatedTag);
                    $this->getService('em')->flush();
                    /*
                     * Dispatch event
                     */
                    $event = new FilterTagEvent($tag);
                    $this->getService('dispatcher')->dispatch(TagEvents::TAG_CREATED, $event);

                    $msg = $this->getTranslator()->trans('child.tag.%name%.created', ['%name%' => $tag->getTagName()]);
                    $this->publishConfirmMessage($request, $msg);

                    return $this->redirect($this->generateUrl(
                        'tagsEditPage',
                        ['tagId' => $tag->getId()]
                    ));
                } catch (EntityAlreadyExistsException $e) {
                    $this->publishErrorMessage($request, $e->getMessage());

                    return $this->redirect($this->generateUrl(
                        'tagsAddChildPage',
                        ['tagId' => $tagId, 'translationId' => $translationId]
                    ));
                }
            }

            $this->assignation['translation'] = $translation;
            $this->assignation['form'] = $form->createView();
            $this->assignation['parentTag'] = $parentTag;

            return $this->render('tags/add.html.twig', $this->assignation);
        } else {
            return $this->throw404();
        }
    }

    /**
     * Handle tag nodes page.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int                                      $tagId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function editNodesAction(Request $request, $tagId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_TAGS');
        $tag = $this->getService('em')
                    ->find('RZ\Roadiz\Core\Entities\Tag', (int) $tagId);

        if (null !== $tag) {
            $translation = $this->getService('em')
                                ->getRepository('RZ\Roadiz\Core\Entities\Translation')
                                ->findDefault();

            $this->assignation['tag'] = $tag;

            /*
             * Manage get request to filter list
             */
            $listManager = new EntityListManager(
                $request,
                $this->getService('em'),
                'RZ\Roadiz\Core\Entities\Node',
                [
                    'tags' => $tag,
                ]
            );
            $listManager->handle();

            $this->assignation['filters'] = $listManager->getAssignation();
            $this->assignation['nodes'] = $listManager->getEntities();

            $this->assignation['translation'] = $translation;

            return $this->render('tags/nodes.html.twig', $this->assignation);

        } else {
            return $this->throw404();
        }
    }

    /**
     * @param array                      $data
     * @param RZ\Roadiz\Core\Entities\Tag $tag
     */
    private function deleteTag($data, Tag $tag)
    {
        $this->getService('em')->remove($tag);
        $this->getService('em')->flush();
    }

    /**
     * @param RZ\Roadiz\Core\Entities\Tag $tag
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildDeleteForm(Tag $tag)
    {
        $builder = $this->createFormBuilder()
                        ->add('tagId', 'hidden', [
                            'data' => $tag->getId(),
                            'constraints' => [
                                new NotBlank(),
                            ],
                        ]);

        return $builder->getForm();
    }
}
