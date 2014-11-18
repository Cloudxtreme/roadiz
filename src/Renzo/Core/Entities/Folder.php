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
 * @file Folder.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\Core\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use RZ\Renzo\Core\AbstractEntities\AbstractDateTimedPositioned;
use RZ\Renzo\Core\Utils\StringHandler;
use RZ\Renzo\Core\Viewers\DocumentViewer;
use RZ\Renzo\Core\Handlers\DocumentHandler;
use RZ\Renzo\Core\Handlers\FolderHandler;

/**
 * Folders entity represent a directory on server with datetime and naming.
 *
 * @Entity(repositoryClass="RZ\Renzo\Core\Repositories\EntityRepository")
 * @Table(name="folders", indexes={
 *     @index(name="position_tag_idx", columns={"position"})
 * })
 */
class Folder extends AbstractDateTimedPositioned
{
    /**
     * @Column(type="string", unique=true, nullable=false)
     */
    private $name;
    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @ManyToOne(targetEntity="RZ\Renzo\Core\Entities\Folder", inversedBy="children")
     * @JoinColumn(name="parent_id", referencedColumnName="id")
     */
    protected $parent = null;

    /**
     * @return RZ\Renzo\Core\Entities\Folder
     */
    public function getParent()
    {
        return $this->parent;
    }
    /**
     * @param RZ\Renzo\Core\Entities\Folder $parent
     */
    public function setParent(Folder $parent = null)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @OneToMany(targetEntity="RZ\Renzo\Core\Entities\Folder", mappedBy="parent")
     */
    protected $children;

    /**
     * @return ArrayCollection
     */
    public function getChildren()
    {
        return $this->children;
    }
    /**
     * @param Folder $child
     */
    public function addChild(Folder $child)
    {
        if (!$this->getChildren()->contains($child)) {
            $this->children->add($child);
        }

        return $this;
    }


    /**
     * @ManyToMany(targetEntity="RZ\Renzo\Core\Entities\Document", inversedBy="folders", fetch="EXTRA_LAZY")
     * @JoinTable(name="documents_folders")
     */
    protected $documents;

    /**
     * @return ArrayCollection
     */
    public function getDocuments()
    {
        return $this->documents;
    }

    /**
     * @param Document $document
     */
    public function addDocument(Document $document)
    {
        if (!$this->getDocuments()->contains($document)) {
            $this->documents->add($document);
        }

        return $this;
    }

    /**
     * Create a new Folder.
     */
    public function __construct()
    {
        $this->children = new ArrayCollection();
        $this->documents = new ArrayCollection();
    }

    /**
     * @return RZ\Renzo\Core\Handlers\FolderHandler
     */
    public function getHandler()
    {
        return new FolderHandler($this);
    }
}
