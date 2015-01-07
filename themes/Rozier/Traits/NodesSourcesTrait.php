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
 * @file NodesSourcesTrait.php
 * @author Maxime Constantinian
 */

namespace Themes\Rozier\Traits;

use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Entities\NodeTypeField;

use Themes\Rozier\RozierApp;
use Themes\Rozier\Traits\NodesSourcesTrait;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Translation\Translator;

trait NodesSourcesTrait
{
    /**
     * Edit node source parameters.
     *
     * @param array                               $data
     * @param RZ\Roadiz\Core\Entities\NodesSources $nodeSource
     *
     * @return void
     */
    private function editNodeSource($data, $nodeSource)
    {
        if (isset($data['title'])) {
            $nodeSource->setTitle($data['title']);

            /*
             * update node name if dynamic option enabled and
             * default translation
             */
            if (true === $nodeSource->getNode()->isDynamicNodeName() &&
                $nodeSource->getTranslation()->isDefaultTranslation()) {
                $testingNodeName = StringHandler::slugify($data['title']);

                /*
                 * node name wont be updated if name already taken
                 */
                if ($testingNodeName != $nodeSource->getNode()->getNodeName() &&
                    false === (boolean) $this->getService('em')->getRepository('RZ\Roadiz\Core\Entities\UrlAlias')->exists($testingNodeName) &&
                    false === (boolean) $this->getService('em')->getRepository('RZ\Roadiz\Core\Entities\Node')->exists($testingNodeName)) {
                    $nodeSource->getNode()->setNodeName($data['title']);
                }
            }
        }

        $fields = $nodeSource->getNode()->getNodeType()->getFields();
        foreach ($fields as $field) {
            if (isset($data[$field->getName()])) {
                static::setValueFromFieldType($data[$field->getName()], $nodeSource, $field);
            } else {
                static::setValueFromFieldType(null, $nodeSource, $field);
            }
        }

        $this->getService('em')->flush();

        // Update Solr Serach engine if setup
        if (true === $this->getKernel()->pingSolrServer()) {
            $solrSource = new \RZ\Roadiz\Core\SearchEngine\SolariumNodeSource(
                $nodeSource,
                $this->getService('solr')
            );
            $solrSource->getDocumentFromIndex();
            $solrSource->updateAndCommit();
        }
    }

    /**
     * @param RZ\Roadiz\Core\Entities\Node         $node
     * @param RZ\Roadiz\Core\Entities\NodesSources $source
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildEditSourceForm(Node $node, $source)
    {
        $fields = $node->getNodeType()->getFields();
        /*
         * Create source default values
         */
        $sourceDefaults = array(
            'title' => $source->getTitle()
        );
        foreach ($fields as $field) {
            if (!$field->isVirtual()) {
                $getter = $field->getGetterName();

                if (method_exists($source, $getter)) {
                    $sourceDefaults[$field->getName()] = $source->$getter();
                } else {
                    throw new \Exception($getter.' method does not exist in '.$node->getNodeType()->getName());
                }
            }
        }

        /*
         * Create subform for source
         */
        $sourceBuilder = $this->getService('formFactory')
            ->createNamedBuilder('source', 'form', $sourceDefaults)
            ->add(
                'title',
                'text',
                array(
                    'label' => $this->getTranslator()->trans('title'),
                    'required' => false,
                    'attr' => array(
                        'data-desc' => ''
                    )
                )
            );
        foreach ($fields as $field) {
            $sourceBuilder->add(
                $field->getName(),
                static::getFormTypeFromFieldType($source, $field, $this),
                static::getFormOptionsFromFieldType($source, $field, $this->getTranslator())
            );
        }

        return $sourceBuilder->getForm();
    }

    /**
     * @param mixed         $nodeSource
     * @param NodeTypeField $field
     * @param AppController $controller
     *
     * @return AbstractType
     */
    public static function getFormTypeFromFieldType($nodeSource, NodeTypeField $field, $controller)
    {
        switch ($field->getType()) {
            case NodeTypeField::DOCUMENTS_T:
                $documents = $nodeSource->getHandler()
                                ->getDocumentsFromFieldName($field->getName());

                return new \RZ\Roadiz\CMS\Forms\DocumentsType($documents);
            case NodeTypeField::NODES_T:
                $nodes = $nodeSource->getNode()->getHandler()
                                ->getNodesFromFieldName($field->getName());

                return new \RZ\Roadiz\CMS\Forms\NodesType($nodes);
            case NodeTypeField::CUSTOM_FORMS_T:
                $customForms = $nodeSource->getNode()->getHandler()
                                ->getCustomFormsFromFieldName($field->getName());

                return new \RZ\Roadiz\CMS\Forms\CustomFormsNodesType($customForms);
            case NodeTypeField::CHILDREN_T:
                /*
                 * NodeTreeType is a virtual type which is only available
                 * with Rozier backend theme.
                 */
                return new \Themes\Rozier\Forms\NodeTreeType(
                    $nodeSource,
                    $field,
                    $controller
                );
            case NodeTypeField::MARKDOWN_T:
                return new \RZ\Roadiz\CMS\Forms\MarkdownType();
            case NodeTypeField::ENUM_T:
                return new \RZ\Roadiz\CMS\Forms\EnumerationType($field);
            case NodeTypeField::MULTIPLE_T:
                return new \RZ\Roadiz\CMS\Forms\MultipleEnumerationType($field);

            default:
                return NodeTypeField::$typeToForm[$field->getType()];
        }
    }

    public static function getFormOptionsFromFieldType(
        $nodeSource,
        NodeTypeField $field,
        Translator $translator
    ) {
        switch ($field->getType()) {
            case NodeTypeField::ENUM_T:
                return array(
                    'label' => $field->getLabel(),
                    'empty_value' => $translator->trans('choose.value'),
                    'required' => false,
                    'attr' => array(
                        'data-desc' => $field->getDescription()
                    )
                );
            case NodeTypeField::DATETIME_T:
                return array(
                    'label' => $field->getLabel(),
                    'years' => range(date('Y')-10, date('Y')+10),
                    'required' => false,
                    'attr' => array(
                        'data-desc' => $field->getDescription(),
                        'class' => 'rz-datetime-field'
                    )
                );
            case NodeTypeField::INTEGER_T:
                return array(
                    'label' => $field->getLabel(),
                    'required' => false,
                    'constraints' => array(
                        new Type('integer')
                    ),
                    'attr' => array(
                        'data-desc' => $field->getDescription()
                    )
                );
            case NodeTypeField::EMAIL_T:
                return array(
                    'label' => $field->getLabel(),
                    'required' => false,
                    'constraints' => array(
                        new \Symfony\Component\Validator\Constraints\Email()
                    ),
                    'attr' => array(
                        'data-desc' => $field->getDescription()
                    )
                );
            case NodeTypeField::DECIMAL_T:
                return array(
                    'label' => $field->getLabel(),
                    'required' => false,
                    'constraints' => array(
                        new Type('double')
                    ),
                    'attr' => array(
                        'data-desc' => $field->getDescription()
                    )
                );
            case NodeTypeField::COLOUR_T:
                return array(
                    'label' => $field->getLabel(),
                    'required' => false,
                    'attr' => array(
                        'data-desc' => $field->getDescription(),
                        'class' => 'colorpicker-input'
                    )
                );
            case NodeTypeField::GEOTAG_T:
                return array(
                    'label' => $field->getLabel(),
                    'required' => false,
                    'attr' => array(
                        'data-desc' => $field->getDescription(),
                        'class' => 'rz-geotag-field'
                    )
                );
            case NodeTypeField::MARKDOWN_T:
                return array(
                    'label' => $field->getLabel(),
                    'required' => false,
                    'attr' => array(
                        'class'           => 'markdown_textarea',
                        'data-desc'       => $field->getDescription(),
                        'data-min-length' => $field->getMinLength(),
                        'data-max-length' => $field->getMaxLength()
                    )
                );

            default:
                return array(
                    'label' => $field->getLabel(),
                    'required' => false,
                    'attr' => array(
                        'data-desc'       => $field->getDescription(),
                        'data-min-length' => $field->getMinLength(),
                        'data-max-length' => $field->getMaxLength()
                    )
                );
        }
    }

    /**
     * Fill node-source content according to field type.
     * @param mixed         $dataValue
     * @param NodesSources  $nodeSource
     * @param NodeTypeField $field
     *
     * @return void
     */
    public static function setValueFromFieldType($dataValue, $nodeSource, NodeTypeField $field)
    {
        switch ($field->getType()) {
            case NodeTypeField::DOCUMENTS_T:
                $hdlr = $nodeSource->getHandler();
                $hdlr->cleanDocumentsFromField($field);
                if (is_array($dataValue)) {
                    foreach ($dataValue as $documentId) {
                        $tempDoc = Kernel::getService('em')
                                        ->find('RZ\Roadiz\Core\Entities\Document', (int) $documentId);
                        if ($tempDoc !== null) {
                            $hdlr->addDocumentForField($tempDoc, $field);
                        }
                    }
                }
                break;
            case NodeTypeField::CUSTOM_FORMS_T:
                $hdlr = $nodeSource->getNode()->getHandler();
                $hdlr->cleanCustomFormsFromField($field);
                if (is_array($dataValue)) {
                    foreach ($dataValue as $customFormId) {
                        $tempCForm = Kernel::getService('em')
                                        ->find('RZ\Roadiz\Core\Entities\CustomForm', (int) $customFormId);
                        if ($tempCForm !== null) {
                            $hdlr->addCustomFormForField($tempCForm, $field);
                        }
                    }
                }
                break;
            case NodeTypeField::NODES_T:
                $hdlr = $nodeSource->getNode()->getHandler();
                $hdlr->cleanNodesFromField($field);

                if (is_array($dataValue)) {
                    foreach ($dataValue as $nodeId) {
                        $tempNode = Kernel::getService('em')
                                        ->find('RZ\Roadiz\Core\Entities\Node', (int) $nodeId);
                        if ($tempNode !== null) {
                            $hdlr->addNodeForField($tempNode, $field);
                        }
                    }
                }
                break;
            case NodeTypeField::CHILDREN_T:
                break;
            default:
                $setter = $field->getSetterName();
                $nodeSource->$setter($dataValue);

                break;
        }
    }
}