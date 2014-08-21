<?php 

namespace RZ\Renzo\Core\Handlers;

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\Node;
use RZ\Renzo\Core\Entities\NodeType;
use RZ\Renzo\Core\Entities\NodeTypeField;
use RZ\Renzo\Core\Entities\Translation;
use RZ\Renzo\Core\Handlers\NodeTypeHandler;
use RZ\Renzo\Core\Serializers\NodeTypeFieldSerializer;

class NodeTypeFieldHandler {

	private $nodeTypeField = null;
	/**
	 * @return NodeTypeField
	 */
	public function getNodeTypeField() {
	    return $this->nodeTypeField;
	}
	/**
	 * @param NodeTypeField $newnodeTypeField
	 */
	public function setNodeTypeField($nodeTypeField) {
	    $this->nodeTypeField = $nodeTypeField;
	    return $this;
	}

	public function generateSourceFieldIndex()
	{
		if (NodeTypeField::$typeToDoctrine[$this->getNodeTypeField()->getType()] !== null) {
			return '@Index(name="'.$this->getNodeTypeField()->getName().'_idx", columns={"'.$this->getNodeTypeField()->getName().'"})';
		}
		else {
			return '';
		}
	}

	public function generateSourceField(){

		if (NodeTypeField::$typeToDoctrine[$this->getNodeTypeField()->getType()] !== null) {
			$var = 'private $'.$this->getNodeTypeField()->getName().';';
			if ($this->getNodeTypeField()->getType() === NodeTypeField::BOOLEAN_T) {
				$var = 'private $'.$this->getNodeTypeField()->getName().' = false;';
			}
			if ($this->getNodeTypeField()->getType() === NodeTypeField::INTEGER_T) {
				$var = 'private $'.$this->getNodeTypeField()->getName().' = 0;';
			}

			return '
	/**
	 * @Column(type="'.NodeTypeField::$typeToDoctrine[$this->getNodeTypeField()->getType()].'", nullable=true )
	 */
	'.$var.'
	public function '.$this->getNodeTypeField()->getGetterName().'() {
	    return $this->'.$this->getNodeTypeField()->getName().';
	}
	public function '.$this->getNodeTypeField()->getSetterName().'($'.$this->getNodeTypeField()->getName().') {
	    $this->'.$this->getNodeTypeField()->getName().' = $'.$this->getNodeTypeField()->getName().';
	
	    return $this;
	}'.PHP_EOL;

		}

		return '';
	}

	/**
     * Serializes data 
     * @return array         
     *//*
    public function serialize() {
        $data = array();

        $data['name'] = $this->getNodeTypeField()->getName();
        $data['label'] = $this->getNodeTypeField()->getLabel();
        $data['description'] = $this->getNodeTypeField()->getDescription();
        $data['visible'] = $this->getNodeTypeField()->isVisible();
        $data['type'] = $this->getNodeTypeField()->getType();
        $data['indexed'] = $this->getNodeTypeField()->isIndexed();
        $data['virtual'] = $this->getNodeTypeField()->isVirtual();

       	return $data;
    }*/

    /**
     * Deserializes a json file into a readable array of datas
     * @return RZ\Renzo\Core\Entities\NodeTypeField
     *//*
    public function deserialize( $jsonString ) {
    	$encoder = new JsonEncoder();
		$normalizer = new GetSetMethodNormalizer();
		$normalizer->setCamelizedAttributes(array(
			'name',
			'label',
			'description',
			'visible',
			'type',
			'indexed',
			'virtual'
		));

		$serializer = new Serializer(array($normalizer), array($encoder));
		return $serializer->deserialize($jsonString, 'RZ\Renzo\Core\Entities\NodeTypeField', 'json');
    }*/

	public function __construct(NodeTypeField $field) {
		$this->nodeTypeField = $field;
	}
}