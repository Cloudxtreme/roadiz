<?php
/**
 * Copyright (c) 2018. Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
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
 * @file ANodesFilter.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */

namespace RZ\Roadiz\Utils\Doctrine\ORM\Filter;

use RZ\Roadiz\Core\Events\FilterNodeQueryBuilderCriteriaEvent;
use RZ\Roadiz\Core\Events\FilterNodesSourcesQueryBuilderCriteriaEvent;
use RZ\Roadiz\Core\Events\FilterQueryBuilderCriteriaEvent;
use RZ\Roadiz\Core\Events\QueryBuilderEvents;
use RZ\Roadiz\Core\Repositories\EntityRepository;
use RZ\Roadiz\Utils\Doctrine\ORM\SimpleQueryBuilder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @package RZ\Roadiz\Utils\Doctrine\ORM\Filter
 */
class ANodesFilter implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            QueryBuilderEvents::QUERY_BUILDER_BUILD_FILTER => [
                // NodesSources should be first as properties are
                // more detailed and precise.
                ['onNodesSourcesQueryBuilderBuild', 40],
                ['onNodeQueryBuilderBuild', 30],
            ]
        ];
    }

    /**
     * @return string
     */
    protected function getProperty()
    {
        return 'aNodes';
    }

    /**
     * @return string
     */
    protected function getNodeJoinAlias()
    {
        return 'a_n';
    }

    /**
     * @return string
     */
    protected function getNodeFieldJoinAlias()
    {
        return 'a_n_f';
    }

    /**
     * @param FilterQueryBuilderCriteriaEvent $event
     */
    public function onNodeQueryBuilderBuild(FilterQueryBuilderCriteriaEvent $event)
    {
        if ($event instanceof FilterNodeQueryBuilderCriteriaEvent &&
            $event->supports()) {
            $simpleQB = new SimpleQueryBuilder($event->getQueryBuilder());
            if (false !== strpos($event->getProperty(), $this->getProperty() . '.')) {
                // Prevent other query builder filters to execute
                $event->stopPropagation();
                $qb = $event->getQueryBuilder();
                $baseKey = $simpleQB->getParameterKey($event->getProperty());

                if (!$simpleQB->joinExists(EntityRepository::NODE_ALIAS, $this->getNodeJoinAlias())) {
                    $qb->innerJoin(
                        EntityRepository::NODE_ALIAS . '.' . $this->getProperty(),
                        $this->getNodeJoinAlias()
                    );
                }
                if (false !== strpos($event->getProperty(), $this->getProperty() . '.field.')) {
                    if (!$simpleQB->joinExists(EntityRepository::NODE_ALIAS, $this->getNodeFieldJoinAlias())) {
                        $qb->innerJoin(
                            $this->getNodeJoinAlias() . '.field',
                            $this->getNodeFieldJoinAlias()
                        );
                    }
                    $prefix = $this->getNodeFieldJoinAlias() . '.';
                    $key = str_replace($this->getProperty() . '.field.', '', $event->getProperty());
                } else {
                    $prefix = $this->getNodeJoinAlias() . '.';
                    $key = str_replace($this->getProperty() . '.', '', $event->getProperty());
                }

                $qb->andWhere($simpleQB->buildExpressionWithoutBinding($event->getValue(), $prefix, $key, $baseKey));
            }
        }
    }

    /**
     * @param FilterQueryBuilderCriteriaEvent $event
     */
    public function onNodesSourcesQueryBuilderBuild(FilterQueryBuilderCriteriaEvent $event)
    {
        if ($event instanceof FilterNodesSourcesQueryBuilderCriteriaEvent &&
            $event->supports()) {
            $simpleQB = new SimpleQueryBuilder($event->getQueryBuilder());
            if (false !== strpos($event->getProperty(), 'node.' . $this->getProperty() . '.')) {
                // Prevent other query builder filters to execute
                $event->stopPropagation();
                $qb = $event->getQueryBuilder();
                $baseKey = $simpleQB->getParameterKey($event->getProperty());

                if (!$simpleQB->joinExists(
                    EntityRepository::NODESSOURCES_ALIAS,
                    EntityRepository::NODE_ALIAS
                )
                ) {
                    $qb->innerJoin(
                        EntityRepository::NODESSOURCES_ALIAS . '.node',
                        EntityRepository::NODE_ALIAS
                    );
                }

                if (!$simpleQB->joinExists(EntityRepository::NODESSOURCES_ALIAS, $this->getNodeJoinAlias())) {
                    $qb->innerJoin(
                        EntityRepository::NODE_ALIAS . '.' . $this->getProperty(),
                        $this->getNodeJoinAlias()
                    );
                }
                if (false !== strpos($event->getProperty(), 'node.' . $this->getProperty() . '.field.')) {
                    if (!$simpleQB->joinExists(EntityRepository::NODESSOURCES_ALIAS, $this->getNodeFieldJoinAlias())) {
                        $qb->innerJoin(
                            $this->getNodeJoinAlias() . '.field',
                            $this->getNodeFieldJoinAlias()
                        );
                    }
                    $prefix = $this->getNodeFieldJoinAlias() . '.';
                    $key = str_replace('node.' . $this->getProperty() . '.field.', '', $event->getProperty());
                } else {
                    $prefix = $this->getNodeJoinAlias() . '.';
                    $key = str_replace('node.' . $this->getProperty() . '.', '', $event->getProperty());
                }

                $qb->andWhere($simpleQB->buildExpressionWithoutBinding($event->getValue(), $prefix, $key, $baseKey));
            }
        }
    }
}
