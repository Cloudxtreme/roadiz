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
 * @file NodesSourcesHandler.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Handlers;

use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodesSourcesDocuments;
use RZ\Roadiz\Core\Entities\NodeTypeField;
use RZ\Roadiz\Core\Kernel;
use Symfony\Component\Security\Core\SecurityContext;

/**
 * Handle operations with node-sources entities.
 */
class NodesSourcesHandler
{
    protected $nodeSource;

    /**
     * Create a new node-source handler with node-source to handle.
     *
     * @param RZ\Roadiz\Core\Entities\NodesSources $nodeSource
     */
    public function __construct($nodeSource)
    {
        $this->nodeSource = $nodeSource;
    }


    /**
     * Remove every node-source documents associations for a given field.
     *
     * @param \RZ\Roadiz\Core\Entities\NodeTypeField $field
     *
     * @return $this
     */
    public function cleanDocumentsFromField(NodeTypeField $field)
    {
        $nsDocuments = Kernel::getService('em')
                ->getRepository('RZ\Roadiz\Core\Entities\NodesSourcesDocuments')
                ->findBy(array('nodeSource'=>$this->nodeSource, 'field'=>$field));

        if (count($nsDocuments) > 0) {
            foreach ($nsDocuments as $nsDoc) {
                Kernel::getService('em')->remove($nsDoc);
            }
            Kernel::getService('em')->flush();
        }

        return $this;
    }

    /**
     * Add a document to current node-source for a given node-type field.
     *
     * @param Document      $document
     * @param NodeTypeField $field
     *
     * @return $this
     */
    public function addDocumentForField(Document $document, NodeTypeField $field)
    {
        $nsDoc = new NodesSourcesDocuments($this->nodeSource, $document, $field);

        $latestPosition = Kernel::getService('em')
                ->getRepository('RZ\Roadiz\Core\Entities\NodesSourcesDocuments')
                ->getLatestPosition($this->nodeSource, $field);

        $nsDoc->setPosition($latestPosition + 1);

        Kernel::getService('em')->persist($nsDoc);
        Kernel::getService('em')->flush();

        return $this;
    }

    /**
     * Get documents linked to current node-source for a given fieldname.
     *
     * @param string $fieldName Name of the node-type field
     *
     * @return ArrayCollection Collection of documents
     */
    public function getDocumentsFromFieldName($fieldName)
    {
        return Kernel::getService('em')
                ->getRepository('RZ\Roadiz\Core\Entities\Document')
                ->findByNodeSourceAndFieldName($this->nodeSource, $fieldName);
    }

    /**
     * @return string Current node-source URL
     */
    public function getUrl($forceHost = false)
    {
        $host = Kernel::getInstance()->getRequest()->getBaseUrl();
        if ($forceHost === true) {
            $host = Kernel::getInstance()->getResolvedBaseUrl();
        }

        if ($this->nodeSource->getNode()->isHome()) {

            if ($this->nodeSource->getTranslation()->isDefaultTranslation()) {
                return $host;
            } else {
                return $host .
                        '/' . $this->nodeSource->getTranslation()->getLocale();
            }
        }

        $urlTokens = array();
        $urlTokens[] = $this->getIdentifier();

        $parent = $this->getParent();
        if ($parent !== null &&
            !$parent->getNode()->isHome()) {

            do {
                $handler = $parent->getHandler();
                $urlTokens[] = $handler->getIdentifier();
                $parent = $parent->getHandler()->getParent();
            } while ($parent !== null && !$parent->getNode()->isHome());
        }

        /*
         * If using node-name, we must use shortLocale when current
         * translation is not the default one.
         */
        if ($urlTokens[0] == $this->nodeSource->getNode()->getNodeName() &&
            !$this->nodeSource->getTranslation()->isDefaultTranslation()) {
            $urlTokens[] = $this->nodeSource->getTranslation()->getLocale();
        }

        $urlTokens[] = $host;
        $urlTokens = array_reverse($urlTokens);

        return implode('/', $urlTokens);
    }

    /**
     * Get a string describing uniquely the curent nodeSource.
     *
     * Can be the urlAlias or the nodeName
     *
     * @return string
     */
    public function getIdentifier()
    {
        $urlalias = $this->nodeSource->getUrlAliases()->first();
        if ($urlalias != null) {
            return $urlalias->getAlias();
        } else {
            return $this->nodeSource->getNode()->getNodeName();
        }
    }

    /**
     * Get parent node-source to get the current translation.
     *
     * @return NodesSources
     */
    public function getParent()
    {
        $parent = $this->nodeSource->getNode()->getParent();
        if ($parent !== null) {
            $query = Kernel::getService('em')
                            ->createQuery('SELECT ns FROM RZ\Roadiz\Core\Entities\NodesSources ns
                                           WHERE ns.node = :node
                                           AND ns.translation = :translation')
                            ->setParameter('node', $parent)
                            ->setParameter('translation', $this->nodeSource->getTranslation());

            try {
                return $query->getSingleResult();
            } catch (\Doctrine\ORM\NoResultException $e) {
                return null;
            }
        } else {
            return null;
        }
    }

    /**
     * Get children nodes sources to lock with current translation.
     *
     * @param array|null                                      $criteria Additionnal criteria
     * @param array|null                                      $order Non default ordering
     * @param Symfony\Component\Security\Core\SecurityContext $securityContext
     *
     * @return ArrayCollection NodesSources collection
     */
    public function getChildren(
        array $criteria = null,
        array $order = null,
        SecurityContext $securityContext = null
    ) {

        $defaultCrit = array(
            'node.parent' => $this->nodeSource->getNode(),
            'node.status' => array('<=', Node::PUBLISHED),
            'translation' => $this->nodeSource->getTranslation()
        );

        if (null !== $order) {
            $defaultOrder = $order;
        } else {
            $defaultOrder = array (
                'node.position' => 'ASC'
            );
        }

        if (null !== $criteria) {
            $defaultCrit = array_merge($defaultCrit, $criteria);
        }

        if (null === $securityContext) {
            $securityContext = Kernel::getService('securityContext');
        }

        return Kernel::getService('em')
                            ->getRepository('RZ\Roadiz\Core\Entities\NodesSources')
                            ->findBy(
                                $defaultCrit,
                                $defaultOrder,
                                null,
                                null,
                                $securityContext
                            );
    }

    /**
     * Get node tags with current source translation.
     *
     * @return ArrayCollection
     */
    public function getTags()
    {
        $tags = Kernel::getService('tagApi')->getBy(array(
            "nodes" => $this->nodeSource->getNode(),
            "translation" => $this->nodeSource->getTranslation()
        ));

        return $tags;
    }
}
