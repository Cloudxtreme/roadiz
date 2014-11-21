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
 * @file NodesSources.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Entities;

use RZ\Roadiz\Core\Handlers\NodesSourcesHandler;
use Doctrine\Common\Collections\ArrayCollection;
use RZ\Roadiz\Core\AbstractEntities\AbstractEntity;

/**
 * NodesSources store Node content according to a translation and a NodeType.
 *
 * @Entity(repositoryClass="RZ\Roadiz\Core\Repositories\NodesSourcesRepository")
 * @Table(name="nodes_sources", uniqueConstraints={@UniqueConstraint(columns={"id","node_id", "translation_id"})})
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="discr", type="string")
 * @HasLifecycleCallbacks
 */
class NodesSources extends AbstractEntity
{
    /**
     * @ManyToOne(targetEntity="Node", inversedBy="nodeSources")
     * @JoinColumn(name="node_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $node;

    /**
     * @return Node
     */
    public function getNode()
    {
        return $this->node;
    }

    /**
     * @param Node $node
     *
     * @return $this
     */
    public function setNode($node)
    {
        $this->node = $node;

        return $this;
    }

    /**
     * @PreUpdate
     */
    public function preUpdate()
    {
        if (null !== $this->getNode()) {
            $this->getNode()->setUpdatedAt(new \DateTime("now"));
        }
    }

    /**
     * @ManyToOne(targetEntity="Translation", inversedBy="nodeSources")
     * @JoinColumn(name="translation_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $translation;
    /**
     * @return Translation
     */
    public function getTranslation()
    {
        return $this->translation;
    }
    /**
     * @param Translation $translation
     *
     * @return $this
     */
    public function setTranslation($translation)
    {
        $this->translation = $translation;

        return $this;
    }

    /**
     * @OneToMany(targetEntity="RZ\Roadiz\Core\Entities\UrlAlias", mappedBy="nodeSource", orphanRemoval=true, fetch="EXTRA_LAZY")
     */
    private $urlAliases = null;
    /**
     * @return ArrayCollection
     */
    public function getUrlAliases()
    {
        return $this->urlAliases;
    }

    /**
     * @OneToMany(targetEntity="RZ\Roadiz\Core\Entities\NodesSourcesDocuments", mappedBy="nodeSource", orphanRemoval=true, fetch="EXTRA_LAZY")
     */
    private $documentsByFields = null;

    /**
     * @return ArrayCollection
     */
    public function getDocumentsByFields()
    {
        return $this->documentsByFields;
    }

    /**
     * @Column(type="string", name="title", unique=false, nullable=true)
     */
    protected $title = '';

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     *
     * @return $this
     */
    public function setTitle($title)
    {
        $this->title = trim($title);

        return $this;
    }

    /**
     * @Column(type="string", name="meta_title", unique=false)
     */
    protected $metaTitle = '';

    /**
     * @return string
     */
    public function getMetaTitle()
    {
        return $this->metaTitle;
    }

    /**
     * @param string $metaTitle
     *
     * @return $this
     */
    public function setMetaTitle($metaTitle)
    {
        $this->metaTitle = trim($metaTitle);

        return $this;
    }
    /**
     * @Column(type="text", name="meta_keywords")
     */
    protected $metaKeywords = '';

    /**
     * @return string
     */
    public function getMetaKeywords()
    {
        return $this->metaKeywords;
    }

    /**
     * @param string $metaKeywords
     *
     * @return $this
     */
    public function setMetaKeywords($metaKeywords)
    {
        $this->metaKeywords = trim($metaKeywords);

        return $this;
    }
    /**
     * @Column(type="text", name="meta_description")
     */
    protected $metaDescription = '';

    /**
     * @return string
     */
    public function getMetaDescription()
    {
        return $this->metaDescription;
    }

    /**
     * @param String $metaDescription
     *
     * @return $this
     */
    public function setMetaDescription($metaDescription)
    {
        $this->metaDescription = trim($metaDescription);

        return $this;
    }

    /**
     * @return NodesSourcesHandler
     */
    public function getHandler()
    {
        return new NodesSourcesHandler($this);
    }

    /**
     * Create a new NodeSource with its Node and Translation.
     *
     * @param Node        $node
     * @param Translation $translation
     */
    public function __construct(Node $node, Translation $translation)
    {
        $this->node = $node;
        $this->translation = $translation;
        $this->urlAliases = new ArrayCollection();
        $this->documentsByFields = new ArrayCollection();
    }

    public function __clone()
    {
        $this->setId(null);
        $documentsByFields = $this->getDocumentsByFields();
        if ($documentsByFields !== null) {
            $this->documentsByFields = new ArrayCollection();
            foreach ($documentsByFields as $documentsByField) {
                $cloneDocumentsByField = clone $documentsByField;
                $this->documentsByFields->add($cloneDocumentsByField);
                $cloneDocumentsByField->setNodeSource($this);
            }
        }
        if ($this->urlAliases !== null) {
            $this->urlAliases->clear();
        }
    }
}
