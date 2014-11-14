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
 * @file NodeTypeJsonSerializer.php
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
 * Json Serialization handler for NodeType.
 */
class NodeTypeJsonSerializer extends AbstractJsonSerializer
{
    /**
     * Create a simple associative array with a NodeType.
     *
     * @param RZ\Renzo\Core\Entities\NodeType $nodeType
     *
     * @return array
     */
    public static function toArray($nodeType)
    {
        $data = array();

        $data['name'] =           $nodeType->getName();
        $data['displayName'] =    $nodeType->getDisplayName();
        $data['description'] =    $nodeType->getDescription();
        $data['visible'] =        $nodeType->isVisible();
        $data['newsletterType'] = $nodeType->isNewsletterType();
        $data['hidingNodes'] =    $nodeType->isHidingNodes();
        $data['fields'] =         array();

        foreach ($nodeType->getFields() as $nodeTypeField) {
            $nodeTypeFieldData = NodeTypeFieldJsonSerializer::toArray($nodeTypeField);

            $data['fields'][] = $nodeTypeFieldData;
        }

        return $data;
    }

    /**
     * Deserializes a Json into readable datas.
     *
     * @param string $string
     *
     * @return RZ\Renzo\Core\Entities\NodeType
     */
    public static function deserialize($string)
    {
        $encoder = new JsonEncoder();
        $normalizer = new GetSetMethodNormalizer();
        $normalizer->setCamelizedAttributes(array(
            'name',
            'displayName',
            'display_name',
            'description',
            'visible',
            'newsletterType',
            'hidingNodes'
        ));

        $serializer = new Serializer(array($normalizer), array($encoder));
        $nodeType = $serializer->deserialize($string, 'RZ\Renzo\Core\Entities\NodeType', 'json');

        /*
         * Importing Fields.
         *
         * We need to extract fields from node-type and to re-encode them
         * to pass to NodeTypeFieldJsonSerializer.
         */
        $tempArray = json_decode($string, true);

        foreach ($tempArray['fields'] as $fieldAssoc) {
            $ntField = NodeTypeFieldJsonSerializer::deserialize(json_encode($fieldAssoc));
            $nodeType->addField($ntField);
        }

        return $nodeType;
    }
}
