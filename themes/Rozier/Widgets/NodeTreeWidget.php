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
 * @file NodeTreeWidget.php
 * @author Ambroise Maupate
 */
namespace Themes\Rozier\Widgets;

use RZ\Roadiz\CMS\Controllers\Controller;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\ListManagers\EntityListManager;
use Symfony\Component\HttpFoundation\Request;
use Themes\Rozier\Widgets\AbstractWidget;

/**
 * Prepare a Node tree according to Node hierarchy and given options.
 *
 * {@inheritdoc}
 */
class NodeTreeWidget extends AbstractWidget
{
    protected $parentNode = null;
    protected $nodes = null;
    protected $tag = null;
    protected $translation = null;
    protected $availableTranslations = null;
    protected $stackTree = false;
    protected $filters = null;
    protected $canReorder = true;

    /**
     * @param Request                            $request           Current kernel request
     * @param RZ\Roadiz\CMS\Controllers\Controller $refereeController Calling controller
     * @param RZ\Roadiz\Core\Entities\Node        $parent            Entry point of NodeTreeWidget, set null if it's root
     * @param RZ\Roadiz\Core\Entities\Translation $translation       NodeTree translation
     */
    public function __construct(
        Request $request,
        Controller $refereeController,
        Node $parent = null,
        Translation $translation = null
    ) {
        parent::__construct($request, $refereeController);

        $this->parentNode = $parent;
        $this->translation = $translation;

        if ($this->translation === null) {
            $this->translation = $this->getController()->getService('em')
                 ->getRepository('RZ\Roadiz\Core\Entities\Translation')
                 ->findDefault();
        }

        $this->availableTranslations = $this->getController()->getService('em')
             ->getRepository('RZ\Roadiz\Core\Entities\Translation')
             ->findAll();
    }

    /**
     * @return RZ\Roadiz\Core\Entities\Tag
     */
    public function getTag()
    {
        return $this->tag;
    }

    /**
     * @param RZ\Roadiz\Core\Entities\Tag $tag
     */
    public function setTag($tag)
    {
        $this->tag = $tag;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isStackTree()
    {
        return $this->stackTree;
    }

    /**
     * @param boolean $newstackTree
     */
    public function setStackTree($newstackTree)
    {
        $this->stackTree = (boolean) $newstackTree;

        return $this;
    }

    /**
     * Fill twig assignation array with NodeTree entities.
     */
    protected function getRootListManager()
    {
        return $this->getListManager($this->parentNode);
    }

    protected function getListManager(Node $parent = null)
    {
        $criteria = [
            'parent' => $parent,
            'translation' => $this->translation,
            'status' => ['<=', Node::PUBLISHED],
        ];

        if (null !== $this->tag) {
            $criteria['tags'] = $this->tag;
        }

        $ordering = [
            'position' => 'ASC',
        ];

        if (null !== $parent &&
            $parent->getChildrenOrder() !== 'order' &&
            $parent->getChildrenOrder() !== 'position') {
            $ordering = [
                $parent->getChildrenOrder() => $parent->getChildrenOrderDirection(),
            ];

            $this->canReorder = false;
        }

        /*
         * Manage get request to filter list
         */
        $listManager = $this->controller->createEntityListManager(
            'RZ\Roadiz\Core\Entities\Node',
            $criteria,
            $ordering
        );

        if (true === $this->stackTree) {
            $listManager->setItemPerPage(20);
            $listManager->handle();
        } else {
            $listManager->setItemPerPage(100);
            $listManager->handle(true);
        }

        return $listManager;
    }
    /**
     * @param RZ\Roadiz\Core\Entities\Node $parent
     *
     * @return ArrayCollection
     */
    public function getChildrenNodes(Node $parent = null)
    {
        return $this->getListManager($parent)->getEntities();
    }
    /**
     * @return RZ\Roadiz\Core\Entities\Node
     */
    public function getRootNode()
    {
        return $this->parentNode;
    }

    public function getFilters()
    {
        return $this->filters;
    }
    /**
     * @return RZ\Roadiz\Core\Entities\Translation
     */
    public function getTranslation()
    {
        return $this->translation;
    }
    /**
     * @return array
     */
    public function getAvailableTranslations()
    {
        return $this->availableTranslations;
    }
    /**
     * @return ArrayCollection
     */
    public function getNodes()
    {
        if (null === $this->nodes) {
            $manager = $this->getRootListManager();
            $this->nodes = $manager->getEntities();
            $this->filters = $manager->getAssignation();
        }

        return $this->nodes;
    }

    /**
     * Gets the value of canReorder.
     *
     * @return boolean
     */
    public function getCanReorder()
    {
        return $this->canReorder;
    }
}
