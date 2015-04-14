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
 * @file TagTreeWidget.php
 * @author Ambroise Maupate
 */
namespace Themes\Rozier\Widgets;

use RZ\Roadiz\CMS\Controllers\Controller;
use RZ\Roadiz\Core\Entities\Tag;
use Symfony\Component\HttpFoundation\Request;
use Themes\Rozier\Widgets\AbstractWidget;

/**
 * Prepare a Tag tree according to Tag hierarchy and given options.
 */
class TagTreeWidget extends AbstractWidget
{
    protected $parentTag = null;
    protected $tags = null;
    protected $translation = null;

    /**
     * @param Request                    $request
     * @param RZ\Roadiz\CMS\Controllers\Controller $refereeController
     * @param RZ\Roadiz\Core\Entities\Tag $parent
     */
    public function __construct(
        Request $request,
        Controller $refereeController,
        Tag $parent = null
    ) {
        parent::__construct($request, $refereeController);

        $this->parentTag = $parent;
        $this->getTagTreeAssignationForParent();
    }

    /**
     * Fill twig assignation array with TagTree entities.
     */
    protected function getTagTreeAssignationForParent()
    {
        if ($this->translation === null) {
            $this->translation = $this->getController()->getService('em')
                 ->getRepository('RZ\Roadiz\Core\Entities\Translation')
                 ->findOneBy(['defaultTranslation' => true]);
        }

        $this->tags = $this->getController()->getService('em')
             ->getRepository('RZ\Roadiz\Core\Entities\Tag')
             ->findBy(
                 ['parent' => $this->parentTag, 'translation' => $this->translation],
                 ['position' => 'ASC']
             );
    }

    /**
     * @param RZ\Roadiz\Core\Entities\Tag $parent
     *
     * @return ArrayCollection
     */
    public function getChildrenTags(Tag $parent)
    {
        if ($this->translation === null) {
            $this->translation = $this->getController()->getService('em')
                 ->getRepository('RZ\Roadiz\Core\Entities\Translation')
                 ->findOneBy(['defaultTranslation' => true]);
        }
        if ($parent !== null) {
            return $this->tags = $this->getController()->getService('em')
                        ->getRepository('RZ\Roadiz\Core\Entities\Tag')
                        ->findBy(['parent' => $parent], ['position' => 'ASC']);
        }

        return null;
    }
    /**
     * @return RZ\Roadiz\Core\Entities\Tag
     */
    public function getRootTag()
    {
        return $this->parentTag;
    }
    /**
     * @return RZ\Roadiz\Core\Entities\Translation
     */
    public function getTranslation()
    {
        return $this->translation;
    }
    /**
     * @return ArrayCollection
     */
    public function getTags()
    {
        return $this->tags;
    }
}
