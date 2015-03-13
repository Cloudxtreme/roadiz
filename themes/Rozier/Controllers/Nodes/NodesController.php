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
 * @file NodesController.php
 * @author Ambroise Maupate
 */
namespace Themes\Rozier\Controllers\Nodes;

use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\Exceptions\EntityAlreadyExistsException;
use RZ\Roadiz\Core\ListManagers\EntityListManager;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\NotBlank;
use Themes\Rozier\RozierApp;
use Themes\Rozier\Traits\NodesTrait;

/**
 * Nodes controller
 *
 * {@inheritdoc}
 */
class NodesController extends RozierApp
{
    use NodesTrait;

    /**
     * List every nodes.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request, $filter = null)
    {
        $this->validateAccessForRole('ROLE_ACCESS_NODES');

        $translation = $this->getService('em')
                            ->getRepository('RZ\Roadiz\Core\Entities\Translation')
                            ->findDefault();

        $user = $this->getService("securityContext")->getToken()->getUser();

        switch ($filter) {
            case 'draft':
                $this->assignation['mainFilter'] = $filter;
                $arrayFilter = [
                    'status' => Node::DRAFT,
                ];
                break;
            case 'pending':
                $this->assignation['mainFilter'] = $filter;
                $arrayFilter = [
                    'status' => Node::PENDING,
                ];
                break;
            case 'archived':
                $this->assignation['mainFilter'] = $filter;
                $arrayFilter = [
                    'status' => Node::ARCHIVED,
                ];
                break;
            case 'deleted':
                $this->assignation['mainFilter'] = $filter;
                $arrayFilter = [
                    'status' => Node::DELETED,
                ];
                break;

            default:

                $this->assignation['mainFilter'] = 'all';
                $arrayFilter = [];
                break;
        }

        if ($user->getChroot() !== null) {
            $arrayFilter["chroot"] = $user->getChroot();
        }

        /*
         * Manage get request to filter list
         */
        $listManager = new EntityListManager(
            $request,
            $this->getService('em'),
            'RZ\Roadiz\Core\Entities\Node',
            $arrayFilter
        );
        $listManager->handle();

        $this->assignation['filters'] = $listManager->getAssignation();
        $this->assignation['nodes'] = $listManager->getEntities();
        $this->assignation['nodeTypes'] = $this->getService('em')
             ->getRepository('RZ\Roadiz\Core\Entities\NodeType')
             ->findBy([
                 'newsletterType' => false,
                 'visible' => true,
             ]);
        $this->assignation['translation'] = $translation;

        return $this->render('nodes/list.html.twig', $this->assignation);
    }

    /**
     * Return an edition form for requested node.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int                                      $nodeId
     * @param int                                      $translationId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Request $request, $nodeId, $translationId = null)
    {
        $this->validateNodeAccessForRole('ROLE_ACCESS_NODES_SETTING', $nodeId);

        $node = $this->getService('em')
                     ->find('RZ\Roadiz\Core\Entities\Node', (int) $nodeId);
        $this->getService('em')->refresh($node);

        $translation = $this->getService('em')
                            ->getRepository('RZ\Roadiz\Core\Entities\Translation')
                            ->findDefault();

        if (null !== $node) {
            $this->assignation['node'] = $node;
            $this->assignation['source'] = $node->getNodeSources()->first();
            $this->assignation['translation'] = $translation;

            /*
             * Handle translation form
             */
            $translationForm = $this->buildTranslateForm($node);
            if (null !== $translationForm) {
                $translationForm->handleRequest();

                if ($translationForm->isValid()) {
                    try {
                        $this->translateNode($translationForm->getData(), $node);
                        $msg = $this->getTranslator()->trans('node.%name%.translated', [
                            '%name%' => $node->getNodeName(),
                        ]);
                        $this->publishConfirmMessage($request, $msg);
                    } catch (EntityAlreadyExistsException $e) {
                        $this->publishErrorMessage($request, $e->getMessage());
                    }

                    $response = new RedirectResponse(
                        $this->getService('urlGenerator')->generate(
                            'nodesEditSourcePage',
                            ['nodeId' => $node->getId(), 'translationId' => $translationForm->getData()['translationId']]
                        )
                    );
                    $response->prepare($request);

                    return $response->send();
                }
                $this->assignation['translationForm'] = $translationForm->createView();
            }

            /*
             * Handle StackTypes form
             */
            $stackTypesForm = $this->buildStackTypesForm($node);
            if (null !== $stackTypesForm) {
                $stackTypesForm->handleRequest();

                if ($stackTypesForm->isValid()) {
                    try {
                        $type = $this->addStackType($stackTypesForm->getData(), $node);
                        $msg = $this->getTranslator()->trans(
                            'stack_node.%name%.has_new_type.%type%',
                            [
                                '%name%' => $node->getNodeName(),
                                '%type%' => $type->getDisplayName(),
                            ]
                        );
                        $this->publishConfirmMessage($request, $msg);

                    } catch (EntityAlreadyExistsException $e) {
                        $this->publishErrorMessage($request, $e->getMessage());
                    }

                    $response = new RedirectResponse(
                        $this->getService('urlGenerator')->generate(
                            'nodesEditPage',
                            ['nodeId' => $node->getId()]
                        )
                    );
                    $response->prepare($request);

                    return $response->send();
                }

                $this->assignation['stackTypesForm'] = $stackTypesForm->createView();
            }

            /*
             * Handle main form
             */
            $form = $this->buildEditForm($node);
            $form->handleRequest();

            if ($form->isValid()) {
                try {
                    $this->editNode($form->getData(), $node);
                    $msg = $this->getTranslator()->trans('node.%name%.updated', [
                        '%name%' => $node->getNodeName(),
                    ]);
                    $this->publishConfirmMessage($request, $msg);
                } catch (EntityAlreadyExistsException $e) {
                    $this->publishErrorMessage($request, $e->getMessage());
                }

                $response = new RedirectResponse(
                    $this->getService('urlGenerator')->generate(
                        'nodesEditPage',
                        ['nodeId' => $node->getId()]
                    )
                );
                $response->prepare($request);

                return $response->send();
            }
            $this->assignation['form'] = $form->createView();
            $this->assignation['securityContext'] = $this->getService("securityContext");

            return $this->render('nodes/edit.html.twig', $this->assignation);
        }

        return $this->throw404();
    }

    /**
     * Handle node creation pages.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int                                      $nodeTypeId
     * @param int                                      $translationId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function addAction(Request $request, $nodeTypeId, $translationId = null)
    {
        $this->validateAccessForRole('ROLE_ACCESS_NODES');

        $type = $this->getService('em')
                     ->find('RZ\Roadiz\Core\Entities\NodeType', $nodeTypeId);

        $translation = $this->getService('em')
                            ->getRepository('RZ\Roadiz\Core\Entities\Translation')
                            ->findDefault();

        if ($translationId !== null) {
            $translation = $this->getService('em')
                                ->find('RZ\Roadiz\Core\Entities\Translation', (int) $translationId);
        }

        if ($type !== null &&
            $translation !== null) {
            $form = $this->getService('formFactory')
                         ->createBuilder()
                         ->add('nodeName', 'text', [
                             'label' => $this->getTranslator()->trans('nodeName'),
                             'constraints' => [
                                 new NotBlank(),
                             ],
                         ])
                         ->getForm();
            $form->handleRequest();

            if ($form->isValid()) {
                try {
                    $node = $this->createNode($form->getData(), $type, $translation);

                    $msg = $this->getTranslator()->trans(
                        'node.%name%.created',
                        ['%name%' => $node->getNodeName()]
                    );
                    $this->publishConfirmMessage($request, $msg);

                    $response = new RedirectResponse(
                        $this->getService('urlGenerator')->generate(
                            'nodesEditPage',
                            ['nodeId' => $node->getId()]
                        )
                    );
                    $response->prepare($request);

                    return $response->send();
                } catch (EntityAlreadyExistsException $e) {
                    $this->publishErrorMessage($request, $e->getMessage());

                    $response = new RedirectResponse(
                        $this->getService('urlGenerator')->generate(
                            'nodesAddPage',
                            ['nodeTypeId' => $nodeTypeId, 'translationId' => $translationId]
                        )
                    );
                    $response->prepare($request);

                    return $response->send();
                }
            }

            $this->assignation['translation'] = $translation;
            $this->assignation['form'] = $form->createView();
            $this->assignation['type'] = $type;
            $this->assignation['nodeTypesCount'] = true;

            return $this->render('nodes/add.html.twig', $this->assignation);
        } else {
            return $this->throw404();
        }
    }

    /**
     * Handle node creation pages.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int                                      $nodeId
     * @param int                                      $translationId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function addChildAction(Request $request, $nodeId = null, $translationId = null)
    {
        // include CHRoot to enable creating node in it
        $this->validateNodeAccessForRole('ROLE_ACCESS_NODES', $nodeId, true);

        $translation = $this->getService('em')
                            ->getRepository('RZ\Roadiz\Core\Entities\Translation')
                            ->findDefault();

        $nodeTypesCount = $this->getService('em')
                               ->getRepository('RZ\Roadiz\Core\Entities\NodeType')
                               ->countBy([]);

        if (null !== $translationId) {
            $translation = $this->getService('em')
                                ->find('RZ\Roadiz\Core\Entities\Translation', (int) $translationId);
        }

        if ($nodeId > 0) {
            $parentNode = $this->getService('em')
                               ->find('RZ\Roadiz\Core\Entities\Node', (int) $nodeId);
        } else {
            $parentNode = null;
        }

        if (null !== $translation) {
            $form = $this->buildAddChildForm($parentNode, $translation);
            $form->handleRequest();

            if ($form->isValid()) {
                try {
                    $node = $this->createChildNode($form->getData(), $parentNode, $translation);

                    $msg = $this->getTranslator()->trans(
                        'node.%name%.created',
                        ['%name%' => $node->getNodeName()]
                    );
                    $this->publishConfirmMessage($request, $msg);

                    $response = new RedirectResponse(
                        $this->getService('urlGenerator')->generate(
                            'nodesEditPage',
                            ['nodeId' => $node->getId()]
                        )
                    );
                    $response->prepare($request);

                    return $response->send();
                } catch (EntityAlreadyExistsException $e) {
                    $this->publishErrorMessage($request, $e->getMessage());

                    $response = new RedirectResponse(
                        $this->getService('urlGenerator')->generate(
                            'nodesAddChildPage',
                            ['nodeId' => $nodeId, 'translationId' => $translationId]
                        )
                    );
                    $response->prepare($request);

                    return $response->send();
                }
            }

            $this->assignation['translation'] = $translation;
            $this->assignation['form'] = $form->createView();
            $this->assignation['parentNode'] = $parentNode;
            $this->assignation['nodeTypesCount'] = $nodeTypesCount;

            return $this->render('nodes/add.html.twig', $this->assignation);
        } else {
            return $this->throw404();
        }
    }

    /**
     * Return an deletion form for requested node.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int                                      $nodeId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction(Request $request, $nodeId)
    {
        $this->validateNodeAccessForRole('ROLE_ACCESS_NODES_DELETE', $nodeId);

        $node = $this->getService('em')
                     ->find('RZ\Roadiz\Core\Entities\Node', (int) $nodeId);

        if (null !== $node &&
            !$node->isDeleted() &&
            !$node->isLocked()) {
            $this->assignation['node'] = $node;

            $form = $this->buildDeleteForm($node);
            $form->handleRequest();

            if ($form->isValid() &&
                $form->getData()['nodeId'] == $node->getId()) {
                $node->getHandler()->softRemoveWithChildren();
                $this->getService('em')->flush();

                // Update Solr Search engine if setup
                if (true === $this->getKernel()->pingSolrServer()) {
                    foreach ($node->getNodeSources() as $nodeSource) {
                        $solrSource = new \RZ\Roadiz\Core\SearchEngine\SolariumNodeSource(
                            $nodeSource,
                            $this->getService('solr')
                        );
                        $solrSource->getDocumentFromIndex();
                        $solrSource->updateAndCommit();
                    }
                }

                $msg = $this->getTranslator()->trans(
                    'node.%name%.deleted',
                    ['%name%' => $node->getNodeName()]
                );
                $this->publishConfirmMessage($request, $msg);
                /*
                 * Force redirect to avoid resending form when refreshing page
                 */
                $response = new RedirectResponse(
                    $this->getService('urlGenerator')->generate('nodesHomePage')
                );
                $response->prepare($request);

                return $response->send();
            }

            $this->assignation['form'] = $form->createView();

            return $this->render('nodes/delete.html.twig', $this->assignation);
        } else {
            return $this->throw404();
        }
    }

    /**
     * Empty trash action.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function emptyTrashAction(Request $request)
    {
        $this->validateAccessForRole('ROLE_ACCESS_NODES_DELETE');

        $form = $this->buildEmptyTrashForm();
        $form->handleRequest();

        if ($form->isValid()) {
            $user = $this->getService("securityContext")->getToken()->getUser();
            $chroot = $user->getChroot();
            $criteria = ['status' => Node::DELETED];
            if ($chroot !== null) {
                $ids = $chroot->getHandler()->getAllOffspringId();
                $criteria["parent"] = $ids;
            }
            $nodes = $this->getService('em')
                          ->getRepository('RZ\Roadiz\Core\Entities\Node')
                          ->findBy($criteria);
            foreach ($nodes as $node) {
                $node->getHandler()->removeWithChildrenAndAssociations();
            }

            $msg = $this->getTranslator()->trans('node.trash.emptied');
            $this->publishConfirmMessage($request, $msg);

            $response = new RedirectResponse(
                $this->getService('urlGenerator')->generate('nodesHomeDeletedPage')
            );
            $response->prepare($request);

            return $response->send();
        }

        $this->assignation['form'] = $form->createView();

        return $this->render('nodes/emptyTrash.html.twig', $this->assignation);
    }
    /**
     * Return an deletion form for requested node.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int                                      $nodeId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function undeleteAction(Request $request, $nodeId)
    {
        $this->validateNodeAccessForRole('ROLE_ACCESS_NODES_DELETE', $nodeId);

        $node = $this->getService('em')
                     ->find('RZ\Roadiz\Core\Entities\Node', (int) $nodeId);

        if (null !== $node &&
            $node->isDeleted()) {
            $this->assignation['node'] = $node;

            $form = $this->buildDeleteForm($node);
            $form->handleRequest();

            if ($form->isValid() &&
                $form->getData()['nodeId'] == $node->getId()) {
                $node->getHandler()->softUnremoveWithChildren();
                $this->getService('em')->flush();

                // Update Solr Search engine if setup
                if (true === $this->getKernel()->pingSolrServer()) {
                    foreach ($node->getNodeSources() as $nodeSource) {
                        $solrSource = new \RZ\Roadiz\Core\SearchEngine\SolariumNodeSource(
                            $nodeSource,
                            $this->getService('solr')
                        );
                        $solrSource->getDocumentFromIndex();
                        $solrSource->updateAndCommit();
                    }
                }

                $msg = $this->getTranslator()->trans(
                    'node.%name%.undeleted',
                    ['%name%' => $node->getNodeName()]
                );
                $this->publishConfirmMessage($request, $msg);
                /*
                 * Force redirect to avoid resending form when refreshing page
                 */
                $response = new RedirectResponse(
                    $this->getService('urlGenerator')->generate('nodesEditPage', [
                        'nodeId' => $node->getId(),
                    ])
                );
                $response->prepare($request);

                return $response->send();
            }

            $this->assignation['form'] = $form->createView();

            return $this->render('nodes/undelete.html.twig', $this->assignation);
        } else {
            return $this->throw404();
        }
    }

    public function generateAndAddNodeAction(Request $request)
    {
        $this->validateAccessForRole('ROLE_ACCESS_NODES');

        if ($request->get('nodeTypeId') > 0 &&
            $request->get('parentNodeId') > 0) {
            $nodeType = $this->getService('em')
                             ->find(
                                 'RZ\Roadiz\Core\Entities\NodeType',
                                 (int) $request->get('nodeTypeId')
                             );

            $parent = $this->getService('em')
                           ->find(
                               'RZ\Roadiz\Core\Entities\Node',
                               (int) $request->get('parentNodeId')
                           );

            if (null !== $nodeType &&
                null !== $parent) {
                $translation = null;

                if ($request->get('translationId') > 0) {
                    $translation = $this->getService('em')->find(
                        'RZ\Roadiz\Core\Entities\Translation',
                        (int) $request->get('translationId')
                    );
                } else {
                    $translation = $parent->getNodeSources()->first()->getTranslation();
                }

                if (null === $translation) {
                    $translation = $this->getService('em')
                                        ->getRepository('RZ\Roadiz\Core\Entities\Translation')
                                        ->findDefault();
                }

                try {
                    $source = static::generateUniqueNodeWithTypeAndTranslation($request, $nodeType, $parent, $translation, null);

                    $response = new RedirectResponse(
                        $this->getService('urlGenerator')->generate(
                            'nodesEditSourcePage',
                            ['nodeId' => $source->getNode()->getId(), 'translationId' => $translation->getId()]
                        )
                    );

                    $response->prepare($request);
                    return $response->send();

                } catch (\Exception $e) {
                    $msg = $this->getTranslator()->trans('node.noCreation.alreadyExists');

                    return $this->throw404($msg);
                }
            }
        }

        return $this->throw404($this->getTranslator()->trans('bad.request'));
    }
}
