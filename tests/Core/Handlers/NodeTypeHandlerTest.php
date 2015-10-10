<?php

use RZ\Roadiz\Core\Handlers\NodeTypeHandler;
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Entities\NodeTypeField;
use RZ\Roadiz\Core\Serializers\NodeTypeJsonSerializer;

class NodeTypeHandlerTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider serializeToJsonProvider
     */
    public function testSerializeToJson($sourceNodeType, $expectedFile)
    {
        $serializer = new NodeTypeJsonSerializer();
        $json = $serializer->serialize($sourceNodeType);

        // Assert
        $this->assertJsonStringEqualsJsonFile($expectedFile, $json);
    }

    public function serializeToJsonProvider()
    {
        // Node type #1
        $nt1 = new NodeType();
        $nt1->setName('page type');

        // Node type #2
        $nt2 = new NodeType();
        $nt2->setName('blog post');
        $nt2->setColor('#FF0000');
        $nt2->setDisplayName('Un blog post');

        $ntf1 = new NodeTypeField();
        $ntf1->setName('Title');
        $ntf1->setType(NodeTypeField::MARKDOWN_T);

        $nt2->addField($ntf1);

        return array(
            array(new NodeType(), ROADIZ_ROOT . '/tests/Fixtures/Handlers/nodeTypeHandler01.json'),
            array($nt1, ROADIZ_ROOT . '/tests/Fixtures/Handlers/nodeTypeHandler02.json'),
            array($nt2, ROADIZ_ROOT . '/tests/Fixtures/Handlers/nodeTypeHandler03.json')
        );
    }
}
