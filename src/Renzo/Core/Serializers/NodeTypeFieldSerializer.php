<?php
/*
 * Copyright REZO ZERO 2014
 *
 * Description
 *
 * @file NodeTypeFieldSerializer.php
 * @copyright REZO ZERO 2014
 * @author Thomas Aufresne
 */

namespace RZ\Renzo\Core\Serializers;

use RZ\Renzo\Core\Entities\NodeTypeField;
use RZ\Renzo\Core\Entities\NodeType;
use RZ\Renzo\Core\Serializers\EntitySerializer;
use RZ\Renzo\Core\Kernel;

use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;

/**
 * Serialization class for NodeTypeField.
 */
class NodeTypeFieldSerializer implements SerializerInterface
{

    protected $nodeTypeField;

    /**
     * NodeTypeFieldSerializer's contructor.
     *
     * @param RZ\Renzo\Core\Entities\NodeTypeField $nodeTypeField
     */
    public function __construct(NodeTypeField $nodeTypeField)
    {
        $this->nodeTypeField = $nodeTypeField;
    }

    /**
     * Serializes data.
     *
     * @return array
     */
    public function serialize()
    {
        $data = array();

        $data['name'] = $this->getNodeTypeField()->getName();
        $data['label'] = $this->getNodeTypeField()->getLabel();
        $data['description'] = $this->getNodeTypeField()->getDescription();
        $data['visible'] = $this->getNodeTypeField()->isVisible();
        $data['type'] = $this->getNodeTypeField()->getType();
        $data['indexed'] = $this->getNodeTypeField()->isIndexed();
        $data['virtual'] = $this->getNodeTypeField()->isVirtual();

        return $data;
    }

    /**
     * Deserializes a json file into a readable array of datas.
     * @param string $jsonString
     *
     * @return RZ\Renzo\Core\Entities\NodeTypeField
     */
    public function deserialize($jsonString)
    {
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
    }
}