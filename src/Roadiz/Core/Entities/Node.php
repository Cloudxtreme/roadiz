<?php
/**
 * Copyright © 2014, REZO ZERO
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
 * Except as contained in this notice, the name of the REZO ZERO shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from the REZO ZERO SARL.
 *
 * @file Node.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use RZ\Roadiz\Core\AbstractEntities\AbstractDateTimedPositioned;

use RZ\Roadiz\Core\Entities\Tag;
use RZ\Roadiz\Core\Utils\StringHandler;
use RZ\Roadiz\Core\Handlers\NodeHandler;
use Doctrine\ORM\Mapping as ORM;

/**
 * Node entities are the central feature of RZ-CMS,
 * it describes a document-like object which can be inherited
 * with *NodesSources* to create complex data structures.
 *
 * @ORM\Entity(repositoryClass="RZ\Roadiz\Core\Repositories\NodeRepository")
 * @ORM\Table(name="nodes", indexes={
 *     @ORM\Index(name="visible_node_idx",       columns={"visible"}),
 *     @ORM\Index(name="status_node_idx",        columns={"status"}),
 *     @ORM\Index(name="locked_node_idx",        columns={"locked"}),
 *     @ORM\Index(name="sterile_node_idx",       columns={"sterile"}),
 *     @ORM\Index(name="position_node_idx",      columns={"position"}),
 *     @ORM\Index(name="hide_children_node_idx", columns={"hide_children"}),
 *     @ORM\Index(name="home_node_idx",          columns={"home"})
 * })
 * @ORM\HasLifecycleCallbacks
 */
class Node extends AbstractDateTimedPositioned
{
    const DRAFT =       10;
    const PENDING =     20;
    const PUBLISHED =   30;
    const ARCHIVED =    40;
    const DELETED =     50;

    /**
     * @ORM\Column(type="string", name="node_name", unique=true)
     */
    private $nodeName;
    /**
     * @return string
     */
    public function getNodeName()
    {
        return $this->nodeName;
    }
    /**
     * @param string $nodeName
     *
     * @return $this
     */
    public function setNodeName($nodeName)
    {
        $this->nodeName = StringHandler::slugify($nodeName);

        return $this;
    }

    /**
     * @ORM\Column(type="boolean", name="home")
     */
    private $home = false;
    /**
     * @return boolean
     */
    public function isHome()
    {
        return (boolean) $this->home;
    }
    /**
     * @param boolean $home
     *
     * @return $this
     */
    public function setHome($home)
    {
        $this->home = (boolean) $home;

        return $this;
    }
    /**
     * @ORM\Column(type="boolean")
     */
    private $visible = true;
    /**
     * @return boolean
     */
    public function isVisible()
    {
        return $this->visible;
    }
    /**
     * @param boolean $visible
     *
     * @return $this
     */
    public function setVisible($visible)
    {
        $this->visible = (boolean) $visible;

        return $this;
    }

    /**
     * @ORM\Column(type="integer")
     */
    private $status = Node::DRAFT;

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param int $newstatus
     */
    public function setStatus($status)
    {
        $this->status = (int) $status;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isPublished()
    {
        return ($this->status === Node::PUBLISHED);
    }
    /**
     * @return boolean
     */
    public function isPending()
    {
        return ($this->status === Node::PENDING);
    }
    /**
     * @return boolean
     */
    public function isDraft()
    {
        return ($this->status === Node::DRAFT);
    }
     /**
     * @return boolean
     */
    public function isDeleted()
    {
        return ($this->status === Node::DELETED);
    }

    /**
     * @param boolean $published
     *
     * @return $this
     */
    public function setPublished($published)
    {
        $this->status = ($published) ? Node::PUBLISHED : Node::PENDING;

        return $this;
    }
    /**
     * @param boolean $pending
     *
     * @return $this
     */
    public function setPending($pending)
    {
        $this->status = ($pending) ? Node::PENDING : Node::DRAFT;

        return $this;
    }
    /**
     * @ORM\Column(type="boolean")
     */
    private $locked = false;

    /**
     * @return boolean
     */
    public function isLocked()
    {
        return $this->locked;
    }

    /**
     * @param boolean $locked
     *
     * @return $this
     */
    public function setLocked($locked)
    {
        $this->locked = (boolean) $locked;

        return $this;
    }

    /**
     * @ORM\Column(type="decimal", precision=2, scale=1)
     */
    private $priority = 0.8;

    /**
     * @return integer
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * @param integer $priority
     *
     * @return $this
     */
    public function setPriority($priority)
    {
        $this->priority = (float)$priority;

        return $this;
    }

    /**
     * @ORM\Column(type="boolean", name="hide_children", nullable=false)
     */
    protected $hideChildren = false;

    /**
     * @return boolean
     */
    public function isHidingChildren()
    {
        return $this->hideChildren;
    }

    /**
     * @param boolean $hideChildren
     *
     * @return $this
     */
    public function setHidingChildren($hideChildren)
    {
        $this->hideChildren = (boolean) $hideChildren;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isArchived()
    {
        return ($this->status === Node::ARCHIVED);
    }
    /**
     * @param boolean $archived
     *
     * @return $this
     */
    public function setArchived($archived)
    {
        $this->archived = ($archived) ? Node::ARCHIVED : Node::PUBLISHED;

        return $this;
    }

    /**
     * @ORM\Column(type="boolean")
     */
    private $sterile = false;
    /**
     * @return boolean
     */
    public function isSterile()
    {
        return $this->sterile;
    }
    /**
     * @param boolean $sterile
     *
     * @return $this
     */
    public function setSterile($sterile)
    {
        $this->sterile = (boolean) $sterile;

        return $this;
    }

    /**
     * @ORM\Column(type="string", name="children_order")
     */
    private $childrenOrder = 'order';

    /**
     * @return string
     */
    public function getChildrenOrder()
    {
        return $this->childrenOrder;
    }

    /**
     * @param string $childrenOrder
     *
     * @return $this
     */
    public function setChildrenOrder($childrenOrder)
    {
        $this->childrenOrder = $childrenOrder;

        return $this;
    }
    /**
     * @ORM\Column(type="string", name="children_order_direction", length=4)
     */
    private $childrenOrderDirection = 'ASC';

    /**
     * @return string
     */
    public function getChildrenOrderDirection()
    {
        return $this->childrenOrderDirection;
    }

    /**
     * @param string $childrenOrderDirection
     *
     * @return $this
     */
    public function setChildrenOrderDirection($childrenOrderDirection)
    {
        $this->childrenOrderDirection = $childrenOrderDirection;

        return $this;
    }

    /**
     * @ORM\ManyToOne(targetEntity="NodeType")
     * @var NodeType
     */
    private $nodeType;

    /**
     * @return NodeType
     */
    public function getNodeType()
    {
        return $this->nodeType;
    }

    /**
     * @param NodeType $nodeType
     *
     * @return $this
     */
    public function setNodeType($nodeType)
    {
        $this->nodeType = $nodeType;

        return $this;
    }

    /**
     * @ORM\ManyToOne(targetEntity="RZ\Roadiz\Core\Entities\Node", inversedBy="children", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="parent_node_id", referencedColumnName="id", onDelete="CASCADE")
     * @var Node
     */
    private $parent;

    /**
     * @return Node Parent node
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @param Node $parent
     *
     * @return $this
     */
    public function setParent($parent)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @ORM\OneToMany(targetEntity="RZ\Roadiz\Core\Entities\Node", mappedBy="parent", orphanRemoval=true, fetch="EXTRA_LAZY")
     * @ORM\OrderBy({"position" = "ASC"})
     * @var ArrayCollection
     */
    private $children;

    /**
     * @return ArrayCollection
     */
    public function getChildren()
    {
        return $this->children;
    }
    /**
     * @param Node $child
     *
     * @return $this
     */
    public function addChild(Node $child)
    {
        if (!$this->getChildren()->contains($child)) {
            $this->getChildren()->add($child);
        }

        return $this;
    }
    /**
     * @param Node $child
     *
     * @return $this
     */
    public function removeChild(Node $child)
    {
        if ($this->getChildren()->contains($child)) {
            $this->getChildren()->removeElement($child);
        }

        return $this;
    }



    /**
     * @ORM\ManyToMany(targetEntity="Tag", inversedBy="nodes", fetch="EXTRA_LAZY")
     * @ORM\JoinTable(name="nodes_tags")
     * @var ArrayCollection
     */
    private $tags = null;
    /**
     * @return ArrayCollection
     */
    public function getTags()
    {
        return $this->tags;
    }
    /**
     * @param Tag $tag
     *
     * @return $this
     */
    public function removeTag(Tag $tag)
    {
        if ($this->getTags()->contains($tag)) {
            $this->getTags()->removeElement($tag);
        }

        return $this;
    }
    /**
     * @param Tag $tag
     *
     * @return $this
     */
    public function addTag(Tag $tag)
    {
        if (!$this->getTags()->contains($tag)) {
            $this->getTags()->add($tag);
        }

        return $this;
    }

    /**
     * @ORM\OneToMany(targetEntity="NodesCustomForms", mappedBy="node", fetch="EXTRA_LAZY")
     * @var ArrayCollection
     */
    private $customForms = null;
    /**
     * @return ArrayCollection
     */
    public function getCustomForms()
    {
        return $this->customForms;
    }


    /**
     * @ORM\ManyToMany(targetEntity="NodeType", fetch="EXTRA_LAZY")
     * @ORM\JoinTable(name="stack_types")
     * @var ArrayCollection
     */
    private $stackTypes = null;
    /**
     * @return ArrayCollection
     */
    public function getStackTypes()
    {
        return $this->stackTypes;
    }
    /**
     * @param NodeType $stackType
     *
     * @return $this
     */
    public function removeStackType(NodeType $stackType)
    {
        if ($this->getStackTypes()->contains($stackType)) {
            $this->getStackTypes()->removeElement($stackType);
        }

        return $this;
    }
    /**
     * @param NodeType $stackType
     *
     * @return $this
     */
    public function addStackType(NodeType $stackType)
    {
        if (!$this->getStackTypes()->contains($stackType)) {
            $this->getStackTypes()->add($stackType);
        }

        return $this;
    }

    /**
     * @ORM\OneToMany(targetEntity="NodesSources", mappedBy="node", orphanRemoval=true, fetch="EXTRA_LAZY")
     */
    private $nodeSources;

    /**
     * @return ArrayCollection
     */
    public function getNodeSources()
    {
        return $this->nodeSources;
    }


    /**
     * @ORM\OneToMany(targetEntity="NodesToNodes", mappedBy="nodeA")
     * @var ArrayCollection
     */
    protected $bNodes;
    public function getBNodes()
    {
        return $this->bNodes;
    }

    /**
     * @ORM\OneToMany(targetEntity="NodesToNodes", mappedBy="nodeB")
     * @var ArrayCollection
     */
    protected $aNodes;
    public function getANodes()
    {
        return $this->aNodes;
    }


    /**
     * Create a new empty Node according to given node-type.
     *
     * @param NodeType $nodeType
     */
    public function __construct(NodeType $nodeType = null)
    {
        $this->tags = new ArrayCollection();
        $this->children = new ArrayCollection();
        $this->nodeSources = new ArrayCollection();
        $this->stackTypes = new ArrayCollection();
        $this->customForms = new ArrayCollection();
        $this->setNodeType($nodeType);
    }
    /**
     * @return string
     */
    public function getOneLineSummary()
    {
        return $this->getId()." — ".$this->getNodeName()." — ".$this->getNodeType()->getName().
            " — Visible : ".($this->isVisible()?'true':'false').PHP_EOL;
    }
    /**
     * @return string
     */
    public function getOneLineSourceSummary()
    {
        $text = "Source ".$this->getDefaultNodeSource()->getId().PHP_EOL;

        foreach ($this->getNodeType()->getFields() as $key => $field) {
            $getterName = 'get'.ucwords($field->getName());
            $text .= '['.$field->getLabel().']: '.$this->getDefaultNodeSource()->$getterName().PHP_EOL;
        }

        return $text;
    }

    /**
     * @ORM\PrePersist
     */
    public function prePersist()
    {
        parent::prePersist();

        /*
         * Get the last index after last node in parent
         */
        $this->setPosition($this->getHandler()->cleanPositions());
    }

    /**
     * @return NodeTypeHandler
     */
    public function getHandler()
    {
        return new NodeHandler($this);
    }

    public function __clone()
    {
        $this->setId(null);
        $this->nodeName .= "-".uniqid();
        $this->home = false;
        $children = $this->getChildren();
        if ($children !== null) {
            $this->children = new ArrayCollection();
            foreach ($children as $child) {
                $cloneChild = clone $child;
                $this->children->add($cloneChild);
                $cloneChild->setParent($this);
            }
        }
        $nodeSources = $this->getNodeSources();
        if ($nodeSources !== null) {
            $this->nodeSources = new ArrayCollection();
            foreach ($nodeSources as $nodeSource) {
                $cloneNodeSource = clone $nodeSource;
                $this->nodeSources->add($cloneNodeSource);
                $cloneNodeSource->setNode($this);
            }
        }
    }
}