<?php
/*
 * Copyright REZO ZERO 2014
 *
 * @file Node.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\Core\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use RZ\Renzo\Core\AbstractEntities\AbstractDateTimedPositioned;

use RZ\Renzo\Core\Entities\Translation;
use RZ\Renzo\Core\Entities\Tag;
use RZ\Renzo\Core\Utils\StringHandler;
use RZ\Renzo\Core\Entities\NodesSources;
use RZ\Renzo\Core\Handlers\NodeHandler;
/**
 * Node entities are the central feature of RZ-CMS,
 * it describes a document-like object which can be inherited
 * with *NodesSources* to create complex data structures.
 *
 * @Entity(repositoryClass="RZ\Renzo\Core\Entities\NodeRepository")
 * @Table(name="nodes", indexes={
 *     @index(name="visible_idx",   columns={"visible"}),
 *     @index(name="published_idx", columns={"published"}),
 *     @index(name="locked_idx",    columns={"locked"}),
 *     @index(name="archived_idx",  columns={"archived"}),
 *     @index(name="position_idx", columns={"position"}),
 *     @index(name="hide_children_idx", columns={"hide_children"}),
 *     @index(name="home_idx", columns={"home"})
 * })
 * @HasLifecycleCallbacks
 */
class Node extends AbstractDateTimedPositioned
{
    /**
     * @Column(type="string", name="node_name", unique=true)
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
     * @Column(type="boolean", name="home")
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
     * @Column(type="boolean")
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
        $this->visible = $visible;

        return $this;
    }
    /**
     * @Column(type="boolean")
     */
    private $published = false;

    /**
     * @return boolean
     */
    public function isPublished()
    {
        return $this->published;
    }

    /**
     * @param boolean $published
     *
     * @return $this
     */
    public function setPublished($published)
    {
        $this->published = $published;

        return $this;
    }
    /**
     * @Column(type="boolean")
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
        $this->locked = $locked;

        return $this;
    }

    /**
     * @Column(type="boolean", name="hide_children", nullable=false)
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
        $this->hideChildren = $hideChildren;

        return $this;
    }

    /**
     * @Column(type="boolean")
     */
    private $archived = false;
    /**
     * @return boolean
     */
    public function isArchived()
    {
        return $this->archived;
    }
    /**
     * @param boolean $archived
     *
     * @return $this
     */
    public function setArchived($archived)
    {
        $this->archived = $archived;

        return $this;
    }

    /**
     * @Column(type="string", name="children_order")
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
     * @Column(type="string", name="children_order_direction", length=4)
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
     * @ManyToOne(targetEntity="NodeType")
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
     * @ManyToOne(targetEntity="RZ\Renzo\Core\Entities\Node", inversedBy="children", fetch="EXTRA_LAZY")
     * @JoinColumn(name="parent_node_id", referencedColumnName="id", onDelete="CASCADE")
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
     * @OneToMany(targetEntity="RZ\Renzo\Core\Entities\Node", mappedBy="parent", orphanRemoval=true, fetch="EXTRA_LAZY")
     * @OrderBy({"position" = "ASC"})
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
     * @ManyToMany(targetEntity="Tag", inversedBy="nodes", orphanRemoval=true, fetch="EXTRA_LAZY")
     * @JoinTable(name="nodes_tags")
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
     * @OneToMany(targetEntity="NodesSources", mappedBy="node", orphanRemoval=true, fetch="EXTRA_LAZY")
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
     * Create a new empty Node according to given node-type.
     *
     * @param NodeType $nodeType
     */
    public function __construct(NodeType $nodeType = null)
    {
        parent::__construct();

        $this->tags = new ArrayCollection();
        $this->childrens = new ArrayCollection();
        $this->nodeSources = new ArrayCollection();
        $this->setNodeType($nodeType);
    }
    /**
     * @todo Move this method to a NodeViewer
     * @return string
     */
    public function getOneLineSummary()
    {
        return $this->getId()." — ".$this->getNodeName()." — ".$this->getNodeType()->getName().
            " — Visible : ".($this->isVisible()?'true':'false').PHP_EOL;
    }
    /**
     * @todo Move this method to a NodeViewer
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
     * @PrePersist
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
}