<?php
/**
 * Copyright © 2015, Ambroise Maupate and Julien Blanchet
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
 * @file NodesSourcesExtension.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Utils\TwigExtensions;

use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Handlers\NodesSourcesHandler;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Twig\Extension\AbstractExtension;

/**
 * Extension that allow to gather nodes-source from hierarchy
 */
class NodesSourcesExtension extends AbstractExtension
{
    protected $preview;
    protected $securityAuthorizationChecker;
    /**
     * @var bool
     */
    private $throwExceptions;
    /**
     * @var NodesSourcesHandler
     */
    private $nodesSourcesHandler;

    /**
     * @param AuthorizationChecker $securityAuthorizationChecker
     * @param NodesSourcesHandler $nodesSourcesHandler
     * @param boolean $preview
     * @param bool $throwExceptions Trigger exception if using filter on NULL values (default: false)
     */
    public function __construct(
        AuthorizationChecker $securityAuthorizationChecker,
        NodesSourcesHandler $nodesSourcesHandler,
        $preview = false,
        $throwExceptions = false
    ) {
        $this->securityAuthorizationChecker = $securityAuthorizationChecker;
        $this->preview = $preview;
        $this->throwExceptions = $throwExceptions;
        $this->nodesSourcesHandler = $nodesSourcesHandler;
    }

    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('children', [$this, 'getChildren']),
            new \Twig_SimpleFilter('next', [$this, 'getNext']),
            new \Twig_SimpleFilter('previous', [$this, 'getPrevious']),
            new \Twig_SimpleFilter('lastSibling', [$this, 'getLastSibling']),
            new \Twig_SimpleFilter('firstSibling', [$this, 'getFirstSibling']),
            new \Twig_SimpleFilter('parent', [$this, 'getParent']),
            new \Twig_SimpleFilter('parents', [$this, 'getParents']),
            new \Twig_SimpleFilter('tags', [$this, 'getTags']),
        ];
    }

    /**
     * @param NodesSources|null $ns
     * @param array|null $criteria
     * @param array|null $order
     * @return array
     * @throws \Twig_Error_Runtime
     */
    public function getChildren(NodesSources $ns = null, array $criteria = null, array $order = null)
    {
        if (null === $ns) {
            if ($this->throwExceptions) {
                throw new \Twig_Error_Runtime("Cannot get children from a NULL node-source.");
            } else {
                return [];
            }
        }
        $this->nodesSourcesHandler->setNodeSource($ns);
        return $this->nodesSourcesHandler->getChildren($criteria, $order);
    }

    /**
     * @param NodesSources|null $ns
     * @param array|null $criteria
     * @param array|null $order
     * @return NodesSources
     * @throws \Twig_Error_Runtime
     */
    public function getNext(NodesSources $ns = null, array $criteria = null, array $order = null)
    {
        if (null === $ns) {
            if ($this->throwExceptions) {
                throw new \Twig_Error_Runtime("Cannot get next sibling from a NULL node-source.");
            } else {
                return null;
            }
        }

        $this->nodesSourcesHandler->setNodeSource($ns);
        return $this->nodesSourcesHandler->getNext($criteria, $order);
    }

    /**
     * @param NodesSources|null $ns
     * @param array|null $criteria
     * @param array|null $order
     * @return NodesSources
     * @throws \Twig_Error_Runtime
     */
    public function getPrevious(NodesSources $ns = null, array $criteria = null, array $order = null)
    {
        if (null === $ns) {
            if ($this->throwExceptions) {
                throw new \Twig_Error_Runtime("Cannot get previous sibling from a NULL node-source.");
            } else {
                return null;
            }
        }

        $this->nodesSourcesHandler->setNodeSource($ns);
        return $this->nodesSourcesHandler->getPrevious($criteria, $order);
    }

    /**
     * @param NodesSources|null $ns
     * @param array|null $criteria
     * @param array|null $order
     * @return NodesSources
     * @throws \Twig_Error_Runtime
     */
    public function getLastSibling(NodesSources $ns = null, array $criteria = null, array $order = null)
    {
        if (null === $ns) {
            if ($this->throwExceptions) {
                throw new \Twig_Error_Runtime("Cannot get last sibling from a NULL node-source.");
            } else {
                return null;
            }
        }

        $this->nodesSourcesHandler->setNodeSource($ns);
        return $this->nodesSourcesHandler->getLastSibling($criteria, $order);
    }

    /**
     * @param NodesSources|null $ns
     * @param array|null $criteria
     * @param array|null $order
     * @return NodesSources
     * @throws \Twig_Error_Runtime
     */
    public function getFirstSibling(NodesSources $ns = null, array $criteria = null, array $order = null)
    {
        if (null === $ns) {
            if ($this->throwExceptions) {
                throw new \Twig_Error_Runtime("Cannot get first sibling from a NULL node-source.");
            } else {
                return null;
            }
        }

        $this->nodesSourcesHandler->setNodeSource($ns);
        return $this->nodesSourcesHandler->getFirstSibling($criteria, $order);
    }

    /**
     * @param NodesSources|null $ns
     * @return NodesSources
     * @throws \Twig_Error_Runtime
     */
    public function getParent(NodesSources $ns = null)
    {
        if (null === $ns) {
            if ($this->throwExceptions) {
                throw new \Twig_Error_Runtime("Cannot get parent from a NULL node-source.");
            } else {
                return null;
            }
        }

        return $ns->getParent();
    }

    /**
     * @param NodesSources|null $ns
     * @param array|null $criteria
     * @param null $preview
     * @return array
     * @throws \Twig_Error_Runtime
     */
    public function getParents(NodesSources $ns = null, array $criteria = null, $preview = null)
    {
        if (null === $ns) {
            if ($this->throwExceptions) {
                throw new \Twig_Error_Runtime("Cannot get parents from a NULL node-source.");
            } else {
                return [];
            }
        }

        $this->nodesSourcesHandler->setNodeSource($ns);
        return $this->nodesSourcesHandler->getParents($criteria);
    }

    /**
     * @param NodesSources|null $ns
     * @return array
     * @throws \Twig_Error_Runtime
     */
    public function getTags(NodesSources $ns = null)
    {
        if (null === $ns) {
            if ($this->throwExceptions) {
                throw new \Twig_Error_Runtime("Cannot get tags from a NULL node-source.");
            } else {
                return [];
            }
        }

        $this->nodesSourcesHandler->setNodeSource($ns);
        return $this->nodesSourcesHandler->getTags();
    }
}
