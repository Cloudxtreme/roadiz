<?php 

namespace RZ\Renzo\Core\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use RZ\Renzo\Core\AbstractEntities\DateTimedPositioned;

use RZ\Renzo\Core\Entities\Translation;
use RZ\Renzo\Core\Entities\NodesSources;
use RZ\Renzo\Core\Handlers\NodeHandler;

/**
 * @Entity(repositoryClass="RZ\Renzo\Core\Entities\NodeRepository")
 * @Table(name="nodes", indexes={
 *     @index(name="visible_idx", columns={"visible"}), 
 *     @index(name="published_idx", columns={"published"}), 
 *     @index(name="locked_idx", columns={"locked"}), 
 *     @index(name="archived_idx", columns={"archived"})
 * })
 */
class Node extends DateTimedPositioned
{
	
	/**
	 * @Column(type="string", name="node_name", unique=true)
	 */
	private $nodeName;
	/**
	 * @return string
	 */
	public function getNodeName() {
	    return $this->nodeName;
	}
	/**
	 * @param string $newnodeName
	 */
	public function setNodeName($nodeName) {
	    $this->nodeName = preg_replace('#([^a-z0-9])#', '-', (trim(strtolower($nodeName))));
	
	    return $this;
	}
	
	/**
	 * @Column(type="boolean")
	 */
	private $visible = true;

	/**
	 * @return boolean
	 */
	public function isVisible() {
	    return $this->visible;
	}
	
	/**
	 * @param boolean $newvisible
	 */
	public function setVisible($visible) {
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
	public function isPublished() {
	    return $this->published;
	}
	
	/**
	 * @param boolean $newpublished
	 */
	public function setPublished($published) {
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
	public function isLocked() {
	    return $this->locked;
	}
	
	/**
	 * @param boolean $newlocked
	 */
	public function setLocked($locked) {
	    $this->locked = $locked;
	
	    return $this;
	}
	/**
	 * @Column(type="boolean")
	 */
	private $archived = false;
	/**
	 * @return boolean
	 */
	public function isArchived() {
	    return $this->archived;
	}
	/**
	 * @param boolean $newarchived
	 */
	public function setArchived($archived) {
	    $this->archived = $archived;
	
	    return $this;
	}

	/**
	 * @Column(type="string", name="children_order")
	 */
	private $childrenOrder = 'order';

	/**
	 * @return [type] [description]
	 */
	public function getChildrenOrder() {
	    return $this->childrenOrder;
	}
	
	/**
	 * @param [type] $newchildrenOrder [description]
	 */
	public function setChildrenOrder($childrenOrder) {
	    $this->childrenOrder = $childrenOrder;
	
	    return $this;
	}
	/**
	 * @Column(type="string", name="children_order_direction", length=4)
	 */
	private $childrenOrderDirection = 'ASC';

	/**
	 * @return [type] [description]
	 */
	public function getChildrenOrderDirection() {
	    return $this->childrenOrderDirection;
	}
	
	/**
	 * @param [type] $newchildrenOrderDirection [description]
	 */
	public function setChildrenOrderDirection($childrenOrderDirection) {
	    $this->childrenOrderDirection = $childrenOrderDirection;
	
	    return $this;
	}

	/**
	 * @ManyToOne(targetEntity="NodeType")
	 * @var NodeType
	 */
	private $nodeType;

	/**
	 * @return [type] [description]
	 */
	public function getNodeType() {
	    return $this->nodeType;
	}
	
	/**
	 * @param [type] $newnodeType [description]
	 */
	public function setNodeType($nodeType) {
	    $this->nodeType = $nodeType;
	
	    return $this;
	}

	/**
	 * @ManyToOne(targetEntity="Node", fetch="EXTRA_LAZY")
	 * @var Node
	 */
	private $parent;

	/**
	 * @return Node parent
	 */
	public function getParent() {
	    return $this->parent;
	}
	
	/**
	 * @param Node $newparent [description]
	 */
	public function setParent($parent) {
	    $this->parent = $parent;
	
	    return $this;
	}

	/**
	 * @OneToMany(targetEntity="Node", mappedBy="parent", orphanRemoval=true, fetch="EXTRA_LAZY")
	 * @var ArrayCollection
	 */
	private $children;

	/**
	 * @return ArrayCollection
	 */
	public function getChildren() {
	    return $this->children;
	}
	/**
	 * @param Node $newchildren
	 * @return Node
	 */
	public function addChild( Node $child ) {
	    $this->children[] = $child;
	    return $this;
	}
	/**
	 * @param  Node   $child 
	 * @return Node
	 */
	public function removeChild( Node $child ) {
        $this->children->removeElement($child);
	    return $this;
    }

	/**
	 * @OneToMany(targetEntity="NodesSources", mappedBy="node", orphanRemoval=true)
	 */
	private $nodeSources;

	/**
	 * @return ArrayCollection
	 */
	public function getNodeSources() {
	    return $this->nodeSources;
	}
	/**
	 * @return NodesSources
	 */
	public function getDefaultNodeSource()
	{
		if (count($this->getNodeSources()) > 0) {
			return $this->getNodeSources()->first();
		}
		return null;
	}
	/**
	 * @param  Translation $translation
	 * @return NodesSources
	 */
	public function getNodeSourceByTranslation( Translation $translation )
	{
		if (count($this->getNodeSources()) > 0) {

			
			$criteria = Criteria::create()
			    ->where(Criteria::expr()->eq("translation", $translation))
			;

			return $this->getNodeSources()->matching($criteria)->first();
		}
		return null;
	}


	/**
	 * @param NodeType $nodeType [description]
	 */
	public function __construct( NodeType $nodeType )
    {
    	parent::__construct();

        $this->childrens = new ArrayCollection();
        $this->nodeSources = new ArrayCollection();
        $this->setNodeType($nodeType);
    }

    public function getOneLineSummary()
	{
		return $this->getId()." — ".$this->getNodeName()." — ".$this->getNodeType()->getName().
			" — Visible : ".($this->isVisible()?'true':'false').PHP_EOL;
	}

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
	 * 
	 * @return NodeTypeHandler
	 */
	public function getHandler()
	{
		return new NodeHandler( $this );
	}
}