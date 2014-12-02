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
 * @file EntityRepository.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Repositories;

use Doctrine\Common\Collections\Criteria;
use RZ\Roadiz\Core\AbstractEntities\PersistableInterface;

/**
 * EntityRepository that implements a simple countBy method.
 */
class EntityRepository extends \Doctrine\ORM\EntityRepository
{
    /**
     * Build a query comparison.
     *
     * @param mixed $value
     * @param string $prefix
     * @param string $key
     * @param string $baseKey
     * @param QueryBuilder $qb
     *
     * @return string
     */
    protected function buildComparison($value, $prefix, $key, $baseKey, &$qb)
    {
        if (is_object($value) && $value instanceof PersistableInterface) {
            $res = $qb->expr()->eq($prefix.$key, ':'.$baseKey);
        } elseif (is_array($value)) {
            /*
             * array
             *
             * ['<=', $value]
             * ['<', $value]
             * ['>=', $value]
             * ['>', $value]
             * ['BETWEEN', $value, $value]
             * ['LIKE', $value]
             * ['NOT IN', $value]
             * in [$value, $value]
             */
            if (count($value) > 1) {
                switch ($value[0]) {
                    case '<=':
                        # lte
                        $res = $qb->expr()->lte($prefix.$key, ':'.$baseKey);
                        break;
                    case '<':
                        # lt
                        $res = $qb->expr()->lt($prefix.$key, ':'.$baseKey);
                        break;
                    case '>=':
                        # gte
                        $res = $qb->expr()->gte($prefix.$key, ':'.$baseKey);
                        break;
                    case '>':
                        # gt
                        $res = $qb->expr()->gt($prefix.$key, ':'.$baseKey);
                        break;
                    case 'BETWEEN':
                        $res = $qb->expr()->between(
                            $prefix.$key,
                            ':'.$baseKey.'_1',
                            ':'.$baseKey.'_2'
                        );
                        break;
                    case 'LIKE':
                        $res = $qb->expr()->like($prefix.$key, $qb->expr()->literal($value[1]));
                        break;
                    case 'NOT IN':
                        $res = $qb->expr()->notIn($prefix.$key, ':'.$baseKey);
                        break;
                    default:
                        $res = $qb->expr()->in($prefix.$key, ':'.$baseKey);
                        break;
                }
            } else {
                $res = $qb->expr()->in($prefix.$key, ':'.$baseKey);
            }

        } elseif (is_bool($value)) {
            $res = $qb->expr()->eq($prefix.$key, ':'.$baseKey);
        } elseif ('NOT NULL' == $value) {
            $res = $qb->expr()->isNotNull($prefix.$key);
        } elseif (isset($value)) {
            $res = $qb->expr()->eq($prefix.$key, ':'.$baseKey);
        } elseif (null === $value) {
            $res = $qb->expr()->isNull($prefix.$key);
        }

        return $res;
    }
    /**
     * Count entities using a Criteria object or a simple filter array.
     *
     * @param mixed $criteria Doctrine\Common\Collections\Criteria or array
     *
     * @return integer
     */
    public function countBy($criteria)
    {
        if ($criteria instanceof Criteria) {
            $collection = $this->matching($criteria);

            return $collection->count();
        } elseif (is_array($criteria)) {
            $expr = Criteria::expr();
            $criteriaObj = Criteria::create();
            $i = 0;

            foreach ($criteria as $key => $value) {

                if (is_array($value)) {
                    $res = $expr->in($key, $value);
                } else {
                    $res = $expr->eq($key, $value);
                }


                if ($i == 0) {
                    $criteriaObj->where($res);
                } else {
                    $criteriaObj->andWhere($res);
                }

                $i++;
            }
            $collection = $this->matching($criteriaObj);

            return $collection->count();
        }
    }

   /**
    * Create a Criteria object from a search pattern and additionnal fields.
    *
    * @param string                  $pattern  Search pattern
    * @param DoctrineORMQueryBuilder $qb       QueryBuilder to pass
    * @param array                   $criteria Additionnal criteria
    * @param string                  $alias    SQL query table alias
    *
    * @return \Doctrine\ORM\QueryBuilder
    */
    protected function createSearchBy(
        $pattern,
        \Doctrine\ORM\QueryBuilder $qb,
        array $criteria = array(),
        $alias = "obj"
    ) {
        /*
         * get fields needed for a search
         * query
         */
        $types = array('string', 'text');
        $metadatas = $this->_em->getClassMetadata($this->getEntityName());
        $criteriaFields = array();
        $cols = $metadatas->getColumnNames();
        foreach ($cols as $col) {
            $field = $metadatas->getFieldName($col);
            $type = $metadatas->getTypeOfField($field);
            if (in_array($type, $types)) {
                $criteriaFields[$field] = '%'.strip_tags($pattern).'%';
            }
        }

        foreach ($criteriaFields as $key => $value) {
            $qb->orWhere($qb->expr()->like($alias . '.' .$key, $qb->expr()->literal($value)));
        }

        foreach ($criteria as $key => $value) {
            if (is_object($value) && $value instanceof PersistableInterface) {
                $res = $qb->expr()->eq($alias . '.' .$key, $value->getId());
            } elseif (is_array($value)) {
                $res = $qb->expr()->in($alias . '.' .$key, $value);
            } elseif (is_bool($value)) {
                $res = $qb->expr()->eq($alias . '.' .$key, (int) $value);
            } else {
                $res = $qb->expr()->eq($alias . '.' .$key, $value);
            }

            $qb->andWhere($res);
        }

        return $qb;
    }

    /**
     * @param string  $pattern  Search pattern
     * @param array   $criteria Additionnal criteria
     * @param array   $orders   [description]
     * @param integer $limit    [description]
     * @param integer $offset   [description]
     *
     * @return Doctrine\Common\Collections\ArrayCollection
     */
    public function searchBy(
        $pattern,
        array $criteria = array(),
        array $orders = array(),
        $limit = null,
        $offset = null
    ) {
        $qb = $this->_em->createQueryBuilder();
        $qb->add('select', 'obj')
           ->add('from', $this->getEntityName() . ' obj');

        $qb = $this->createSearchBy($pattern, $qb, $criteria, 'obj');

        // Add ordering
        foreach ($orders as $key => $value) {
            $qb->addOrderBy('obj.'.$key, $value);
        }

        if (null !== $offset) {
            $qb->setFirstResult($offset);
        }
        if (null !== $limit) {
            $qb->setMaxResults($limit);
        }

        $finalQuery = $qb->getQuery();

        try {
            return $finalQuery->getResult();
        } catch (\Doctrine\ORM\Query\QueryException $e) {
            return null;
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }

    /**
     * @param string $pattern  Search pattern
     * @param array  $criteria Additionnal criteria
     *
     * @return Doctrine\Common\Collections\ArrayCollection
     */
    public function countSearchBy($pattern, array $criteria = array())
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->add('select', 'count(obj)')
           ->add('from', $this->getEntityName() . ' obj');

        $qb = $this->createSearchBy($pattern, $qb, $criteria);

        try {
            return $qb->getQuery()->getSingleScalarResult();
        } catch (\Doctrine\ORM\Query\QueryException $e) {
            return null;
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }
}
