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
 * @file TagRepository.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Repositories;

use RZ\Roadiz\Core\Entities\Tag;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\Entities\TagTranslation;
use RZ\Roadiz\Core\AbstractEntities\PersistableInterface;
use Symfony\Component\Security\Core\SecurityContext;

/**
 * {@inheritdoc}
 */
class TagRepository extends EntityRepository
{
    /**
     * Add a node filtering to queryBuilder.
     *
     * @param array        $criteria
     * @param QueryBuilder $qb
     */
    protected function filterByNodes(&$criteria, &$qb)
    {
        if (in_array('nodes', array_keys($criteria))) {

            if (is_array($criteria['nodes'])) {
                $qb->innerJoin(
                    'tg.nodes',
                    'n',
                    'WITH',
                    'n.id IN (:nodes)'
                );
            } else {
                $qb->innerJoin(
                    'tg.nodes',
                    'n',
                    'WITH',
                    'n.id = :nodes'
                );
            }
        }
    }

    /**
     * Bind node parameter to final query
     *
     * @param array $criteria
     * @param Query $finalQuery
     */
    protected function applyFilterByNodes(array &$criteria, &$finalQuery)
    {
        if (in_array('nodes', array_keys($criteria))) {
            if (is_object($criteria['nodes'])) {
                $finalQuery->setParameter('nodes', $criteria['nodes']->getId());
            } elseif (is_array($criteria['nodes'])) {
                $finalQuery->setParameter('nodes', $criteria['nodes']);
            } elseif (is_integer($criteria['nodes'])) {
                $finalQuery->setParameter('nodes', (int) $criteria['nodes']);
            }
            unset($criteria['nodes']);
        }
    }

    /**
     * Reimplementing findBy features… with extra things
     *
     * * key => array('<=', $value)
     * * key => array('<', $value)
     * * key => array('>=', $value)
     * * key => array('>', $value)
     * * key => array('BETWEEN', $value, $value)
     * * key => array('LIKE', $value)
     * * key => array('NOT IN', $array)
     * * key => 'NOT NULL'
     *
     * You can filter with translations relation, examples:
     *
     * * `translation => $object`
     * * `translation.locale => 'fr_FR'`
     *
     * @param array        $criteria
     * @param QueryBuilder $qb
     */
    protected function filterByCriteria(&$criteria, &$qb)
    {
        /*
         * Reimplementing findBy features…
         */
        foreach ($criteria as $key => $value) {
            /*
             * Search in node fields
             */
            if ($key == 'nodes') {
                continue;
            }

            /*
             * compute prefix for
             * filtering node, and sources relation fields
             */
            $prefix = 'tg.';

            // Dots are forbidden in field definitions
            $baseKey = str_replace('.', '_', $key);
            /*
             * Search in translation fields
             */
            if (false !== strpos($key, 'translation.')) {
                $prefix = 't.';
                $key = str_replace('translation.', '', $key);
            }

            /*
             * Search in node fields
             */
            if (false !== strpos($key, 'nodes.')) {
                $prefix = 'n.';
                $key = str_replace('nodes.', '', $key);
            }

            /*
             * Search in translatedTags fields
             */
            if (false !== strpos($key, 'translatedTag.')) {
                $prefix = 'tt.';
                $key = str_replace('translatedTag.', '', $key);
            }


             /*
             * Search in translation fields
             */
            if ($key == 'translation') {
                $prefix = 'tt.';
            }

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

            $qb->andWhere($res);
        }
    }
    /**
     * Bind parameters to generated query.
     *
     * @param array $criteria
     * @param Query $finalQuery
     */
    protected function applyFilterByCriteria(&$criteria, &$finalQuery)
    {
        /*
         * Reimplementing findBy features…
         */
        foreach ($criteria as $key => $value) {

            // Dots are forbidden in field definitions
            $key = str_replace('.', '_', $key);

            if (is_object($value) && $value instanceof PersistableInterface) {
                $finalQuery->setParameter($key, $value->getId());
            } elseif (is_array($value)) {

                if (count($value) > 1) {
                    switch ($value[0]) {
                        case '<=':
                        case '<':
                        case '>=':
                        case '>':
                        case 'NOT IN':
                            $finalQuery->setParameter($key, $value[1]);
                            break;
                        case 'BETWEEN':
                            $finalQuery->setParameter($key.'_1', $value[1]);
                            $finalQuery->setParameter($key.'_2', $value[2]);
                            break;
                        case 'LIKE':
                            // param is setted in filterBy
                            break;
                        default:
                            $finalQuery->setParameter($key, $value);
                            break;
                    }
                } else {
                    $finalQuery->setParameter($key, $value);
                }

            } elseif (is_bool($value)) {
                 $finalQuery->setParameter($key, $value);
            } elseif ('NOT NULL' == $value) {
                // param is not needed
            } elseif (isset($value)) {
                $finalQuery->setParameter($key, $value);
            } elseif (null === $value) {
                // param is not needed
            }
        }
    }

    /**
     * Create filters according to any translation criteria OR argument.
     *
     * @param array        $criteria
     * @param QueryBuilder $qb
     * @param Translation  $translation
     */
    protected function filterByTranslation(&$criteria, &$qb, &$translation = null)
    {
        if (isset($criteria['translation']) ||
            isset($criteria['translation.locale']) ||
            isset($criteria['translation.id'])) {

            $qb->innerJoin('tg.translatedTags', 'tt');
            $qb->innerJoin('tt.translation', 't');

        } else {

            if (null !== $translation) {
                /*
                 * With a given translation
                 */
                $qb->innerJoin(
                    'tg.translatedTags',
                    'tt',
                    'WITH',
                    'tt.translation = :translation'
                );
            } else {
                /*
                 * With a null translation, just take the default one.
                 */
                $qb->innerJoin('tg.translatedTags', 'tt');
                $qb->innerJoin(
                    'tt.translation',
                    't',
                    'WITH',
                    't.defaultTranslation = true'
                );
            }
        }
    }

    /**
     * Bind translation parameter to final query
     *
     * @param array $criteria
     * @param Query $finalQuery
     */
    protected function applyTranslationByTag(
        array &$criteria,
        &$finalQuery,
        &$translation = null
    ) {
        if (null !== $translation) {
            $finalQuery->setParameter('translation', $translation);
        }
    }


    /**
     * This method allows to pre-filter Nodes with a given translation.
     *
     * @param array                                   $criteria
     * @param array|null                              $orderBy
     * @param integer|null                            $limit
     * @param integer|null                            $offset
     * @param RZ\Roadiz\Core\Entities\Translation|null $securityContext
     *
     * @return QueryBuilder
     */
    protected function getContextualQueryWithTranslation(
        array $criteria,
        array $orderBy = null,
        $limit = null,
        $offset = null,
        Translation $translation = null
    ) {

        $qb = $this->_em->createQueryBuilder();
        $qb->add('select', 'tg, tt')
           ->add('from', $this->getEntityName() . ' tg');

        $this->filterByNodes($criteria, $qb);
        $this->filterByTranslation($criteria, $qb, $translation);
        $this->filterByCriteria($criteria, $qb);

        // Add ordering
        if (null !== $orderBy) {
            foreach ($orderBy as $key => $value) {
                $qb->addOrderBy('tg.'.$key, $value);
            }
        }

        if (null !== $offset) {
            $qb->setFirstResult($offset);
        }
        if (null !== $limit) {
            $qb->setMaxResults($limit);
        }

        return $qb;
    }
    /**
     * This method allows to pre-filter Nodes with a given translation.
     *
     * @param array                                   $criteria
     * @param RZ\Roadiz\Core\Entities\Translation|null $securityContext
     *
     * @return QueryBuilder
     */
    protected function getCountContextualQueryWithTranslation(
        array $criteria,
        Translation $translation = null
    ) {

        $qb = $this->_em->createQueryBuilder();
        $qb->add('select', 'count(tg.id)')
           ->add('from', $this->getEntityName() . ' tg');

        $this->filterByNodes($criteria, $qb);
        $this->filterByTranslation($criteria, $qb, $translation);
        $this->filterByCriteria($criteria, $qb);

        return $qb;
    }

    /**
     * Just like the findBy method but with relational criteria.
     *
     * @param array                                   $criteria
     * @param array|null                              $orderBy
     * @param integer|null                            $limit
     * @param integer|null                            $offset
     * @param RZ\Roadiz\Core\Entities\Translation|null $translation
     *
     * @return Doctrine\Common\Collections\ArrayCollection
     */
    public function findBy(
        array $criteria,
        array $orderBy = null,
        $limit = null,
        $offset = null,
        Translation $translation = null,
        SecurityContext $securityContext = null
    ) {
        $query = $this->getContextualQueryWithTranslation(
            $criteria,
            $orderBy,
            $limit,
            $offset,
            $translation
        );

        $finalQuery = $query->getQuery();

        $this->applyFilterByNodes($criteria, $finalQuery);
        $this->applyFilterByCriteria($criteria, $finalQuery);
        $this->applyTranslationByTag($criteria, $finalQuery, $translation);

        try {
            return $finalQuery->getResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }
    /**
     * Just like the findOneBy method but with relational criteria.
     *
     * @param array                                   $criteria
     * @param array|null                              $orderBy
     * @param RZ\Roadiz\Core\Entities\Translation|null $translation
     * @param SecurityContext|null                    $securityContext
     *
     * @return Doctrine\Common\Collections\ArrayCollection
     */
    public function findOneBy(
        array $criteria,
        array $orderBy = null,
        Translation $translation = null,
        SecurityContext $securityContext = null
    ) {

        $query = $this->getContextualQueryWithTranslation(
            $criteria,
            $orderBy,
            1,
            0,
            $translation
        );

        $finalQuery = $query->getQuery();

        $this->applyFilterByNodes($criteria, $finalQuery);
        $this->applyFilterByCriteria($criteria, $finalQuery);
        $this->applyTranslationByTag($criteria, $finalQuery, $translation);

        try {
            return $finalQuery->getSingleResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }
    /**
     * Just like the countBy method but with relational criteria.
     *
     * @param array                                   $criteria
     * @param RZ\Roadiz\Core\Entities\Translation|null $translation
     * @param SecurityContext|null                    $securityContext
     *
     * @return int
     */
    public function countBy(
        $criteria,
        Translation $translation = null
    ) {
        $query = $this->getCountContextualQueryWithTranslation(
            $criteria,
            $translation
        );

        $finalQuery = $query->getQuery();

        $this->applyFilterByNodes($criteria, $finalQuery);
        $this->applyFilterByCriteria($criteria, $finalQuery);
        $this->applyTranslationByTag($criteria, $finalQuery, $translation);

        try {
            return $finalQuery->getSingleScalarResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }

    /**
     * @param integer                            $tagId
     * @param RZ\Roadiz\Core\Entities\Translation $translation
     *
     * @return RZ\Roadiz\Core\Entities\Tag
     */
    public function findWithTranslation($tagId, Translation $translation)
    {
        $query = $this->_em->createQuery('
            SELECT t, tt FROM RZ\Roadiz\Core\Entities\Tag t
            INNER JOIN t.translatedTags tt
            WHERE t.id = :tag_id
            AND tt.translation = :translation')
                        ->setParameter('tag_id', (int) $tagId)
                        ->setParameter('translation', $translation);

        try {
            return $query->getSingleResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }

    /**
     * @param RZ\Roadiz\Core\Entities\Translation $translation
     *
     * @return ArrayCollection
     */
    public function findAllWithTranslation(Translation $translation)
    {
        $query = $this->_em->createQuery('
            SELECT tg, tt FROM RZ\Roadiz\Core\Entities\Tag tg
            INNER JOIN tg.translatedTags tt
            WHERE tt.translation = :translation')
                        ->setParameter('translation', $translation);

        try {
            return $query->getResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }

    /**
     * @param integer $tagId
     *
     * @return RZ\Roadiz\Core\Entities\Tag
     */
    public function findWithDefaultTranslation($tagId)
    {
        $query = $this->_em->createQuery('
            SELECT t, tt FROM RZ\Roadiz\Core\Entities\Tag t
            INNER JOIN t.translatedTags tt
            INNER JOIN tt.translation tr
            WHERE t.id = :tag_id
            AND tr.defaultTranslation = true')
                        ->setParameter('tag_id', (int) $tagId);

        try {
            return $query->getSingleResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }

    /**
     * @return ArrayCollection
     */
    public function findAllWithDefaultTranslation()
    {
        $query = $this->_em->createQuery('
            SELECT t, tt FROM RZ\Roadiz\Core\Entities\Tag t
            INNER JOIN t.translatedTags tt
            INNER JOIN tt.translation tr
            WHERE tr.defaultTranslation = true');
        try {
            return $query->getResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }

    /**
     * @param RZ\Roadiz\Core\Entities\Translation $translation
     * @param RZ\Roadiz\Core\Entities\Tag         $parent
     *
     * @return array Doctrine result array
     */
    public function findByParentWithTranslation(Translation $translation, Tag $parent = null)
    {
        $query = null;

        if ($parent === null) {
            $query = $this->_em->createQuery('
            SELECT t, tt FROM RZ\Roadiz\Core\Entities\Tag t
            INNER JOIN t.translatedTags tt
            INNER JOIN tt.translation tr
            WHERE t.parent IS NULL AND tr.id = :translation_id
            ORDER BY t.position ASC')
                            ->setParameter('translation_id', (int) $translation->getId());
        } else {
            $query = $this->_em->createQuery('
                SELECT t, tt FROM RZ\Roadiz\Core\Entities\Tag t
                INNER JOIN t.translatedTags tt
                INNER JOIN tt.translation tr
                INNER JOIN t.parent pt
                WHERE pt.id = :parent AND tr.id = :translation_id
                ORDER BY t.position ASC')
                            ->setParameter('parent', $parent->getId())
                            ->setParameter('translation_id', (int) $translation->getId());
        }

        try {
            return $query->getResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }

    /**
     * @param RZ\Roadiz\Core\Entities\Tag $parent
     *
     * @return ArrayCollection
     */
    public function findByParentWithDefaultTranslation(Tag $parent = null)
    {
        $query = null;
        if ($parent === null) {
            $query = $this->_em->createQuery('
            SELECT t, tt FROM RZ\Roadiz\Core\Entities\Tag t
            INNER JOIN t.translatedTags tt
            INNER JOIN tt.translation tr
            WHERE t.parent IS NULL AND tr.defaultTranslation = true
            ORDER BY t.position ASC');
        } else {
            $query = $this->_em->createQuery('
                SELECT t, tt FROM RZ\Roadiz\Core\Entities\Tag t
                INNER JOIN t.translatedTags tt
                INNER JOIN tt.translation tr
                INNER JOIN t.parent pt
                WHERE pt.id = :parent AND tr.defaultTranslation = true
                ORDER BY t.position ASC')
                            ->setParameter('parent', $parent->getId());
        }

        try {
            return $query->getResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
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

        /*
         * Search in tag fields
         */
        $criteriaFields = array();
        $metadatas = $this->_em->getClassMetadata($this->getEntityName());
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

        /*
         * Search in translations
         */
        $qb->leftJoin('obj.translatedTags', 'tt');
        $criteriaFields = array();
        $metadatas = $this->_em->getClassMetadata('RZ\Roadiz\Core\Entities\TagTranslation');
        $cols = $metadatas->getColumnNames();
        foreach ($cols as $col) {
            $field = $metadatas->getFieldName($col);
            $type = $metadatas->getTypeOfField($field);
            if (in_array($type, $types)) {
                $criteriaFields[$field] = '%'.strip_tags($pattern).'%';
            }
        }
        foreach ($criteriaFields as $key => $value) {
            $qb->orWhere($qb->expr()->like('tt.' .$key, $qb->expr()->literal($value)));
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
     * @param string $pattern  Search pattern
     * @param array  $criteria Additionnal criteria
     *
     * @return Doctrine\Common\Collections\ArrayCollection
     */
    public function countSearchBy($pattern, array $criteria = array())
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->add('select', 'count(t)')
           ->add('from', $this->getEntityName() . ' t')
           ->innerJoin('t.translatedTags', 'obj');

        $qb = $this->createSearchBy($pattern, $qb, $criteria);

        try {
            return $qb->getQuery()->getSingleScalarResult();
        } catch (\Doctrine\ORM\Query\QueryException $e) {
            return null;
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }

    /**
     * Find a tag according to the given path or create it.
     *
     * @param string $tagPath
     *
     * @return RZ\Roadiz\Core\Entities\Tag
     */
    public function findOrCreateByPath($tagPath)
    {
        $tagPath = trim($tagPath);
        $tags = explode('/', $tagPath);
        $tags = array_filter($tags);

        $tagName = $tags[count($tags) - 1];
        $parentName = null;
        $parentTag = null;

        if (count($tags) > 1) {
            $parentName = $tags[count($tags) - 2];

            $parentTag = $this->findOneByTagName($parentName);

            if (null === $parentTag) {
                $ttagParent = $this->_em
                            ->getRepository('RZ\Roadiz\Core\Entities\TagTranslation')
                            ->findOneByName($parentName);
                if (null !== $ttagParent) {
                    $parentTag = $ttagParent->getTag();
                }
            }
        }

        $tag = $this->findOneByTagName($tagName);


        if (null === $tag) {
            $ttag = $this->_em
                        ->getRepository('RZ\Roadiz\Core\Entities\TagTranslation')
                        ->findOneByName($tagName);
            if (null !== $ttag) {
                $tag = $ttag->getTag();
            }
        }

        if (null === $tag) {

            /*
             * Creation of a new tag
             * before linking it to the node
             */
            $trans = $this->_em
                        ->getRepository('RZ\Roadiz\Core\Entities\Translation')
                        ->findDefault();

            $tag = new Tag();
            $tag->setTagName($tagName);
            $translatedTag = new TagTranslation($tag, $trans);
            $translatedTag->setName($tagName);
            $tag->getTranslatedTags()->add($translatedTag);

            if (null !== $parentTag) {
                $tag->setParent($parentTag);
            }

            $this->_em->persist($translatedTag);
            $this->_em->persist($tag);
            $this->_em->flush();
        }

        return $tag;
    }

    /**
     * Find a tag according to the given path.
     *
     * @param string $tagPath
     *
     * @return RZ\Roadiz\Core\Entities\Tag|null
     */
    public function findByPath($tagPath)
    {
        $tagPath = trim($tagPath);
        $tags = explode('/', $tagPath);
        $tags = array_filter($tags);

        $lastToken = count($tags) - 1;

        $tagName = count($tags) > 0 ? $tags[$lastToken] : $tagPath;

        $parentName = null;
        $parentTag = null;

        if (count($tags) > 1) {
            $parentName = $tags[count($tags) - 2];

            $parentTag = $this->findOneByTagName($parentName);

            if (null === $parentTag) {
                $ttagParent = $this->_em
                            ->getRepository('RZ\Roadiz\Core\Entities\TagTranslation')
                            ->findOneByName($parentName);
                if (null !== $ttagParent) {
                    $parentTag = $ttagParent->getTag();
                }
            }
        }

        $tag = $this->findOneByTagName($tagName);


        if (null === $tag) {
            $ttag = $this->_em
                        ->getRepository('RZ\Roadiz\Core\Entities\TagTranslation')
                        ->findOneByName($tagName);
            if (null !== $ttag) {
                $tag = $ttag->getTag();
            }
        }


        return $tag;
    }
}
