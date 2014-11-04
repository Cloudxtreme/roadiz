<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 *
 * @file NodeApi.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\CMS\Utils;

use RZ\Renzo\CMS\Utils\AbstractApi;
use RZ\Renzo\Core\Entities\Node;

/**
 *
 */
class NodeApi extends AbstractApi
{
    public function getRepository()
    {
        return $this->container['em']->getRepository("RZ\Renzo\Core\Entities\Node");
    }

    public function getBy(
        array $criteria,
        array $order = null,
        $limit = null,
        $offset = null
    ) {
        if (empty($criteria['status'])) {
            $criteria['status'] = array('<=', Node::PUBLISHED);
        }

        return $this->container['em']
                    ->getRepository("RZ\Renzo\Core\Entities\Node")
                    ->findBy(
                        $criteria,
                        $order,
                        $limit,
                        $offset,
                        null,
                        $this->container['securityContext']
                    );
    }

    public function countBy(array $criteria)
    {
        if (empty($criteria['status'])) {
            $criteria['status'] = array('<=', Node::PUBLISHED);
        }

        return $this->container['em']
                    ->getRepository("RZ\Renzo\Core\Entities\Node")
                    ->countBy(
                        $criteria,
                        null,
                        $this->container['securityContext']
                    );
    }

    public function getOneBy(array $criteria, array $order = null)
    {
        if (empty($criteria['status'])) {
            $criteria['status'] = array('<=', Node::PUBLISHED);
        }

        return $this->container['em']
                    ->getRepository("RZ\Renzo\Core\Entities\Node")
                    ->findOneBy(
                        $criteria,
                        $order,
                        null,
                        $this->container['securityContext']
                    );
    }
}
