<?php 


namespace RZ\Renzo\Core\Entities;

use Doctrine\ORM\EntityRepository;

use RZ\Renzo\Core\Entities\Translation;
use RZ\Renzo\Core\Kernel;

/**
* 
*/
class NodeRepository extends EntityRepository
{	
	/**
	 * 
	 * @param  integer      $node_id     [description]
	 * @param  Translation $translation [description]
	 * @return Node or null
	 */
	public function findWithTranslation($node_id, Translation $translation )
	{
	    $query = Kernel::getInstance()->em()
                        ->createQuery('
            SELECT n, ns FROM RZ\Renzo\Core\Entities\Node n 
            INNER JOIN n.nodeSources ns 
            INNER JOIN ns.translation t
            WHERE n.id = :node_id AND t.id = :translation_id'
                        )->setParameter('node_id', (int)$node_id)
                        ->setParameter('translation_id', (int)$translation->getId());

        try {
            return $query->getSingleResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
	}

    /**
     * 
     * @param  integer      $node_id     [description]
     * @return Node or null
     */
    public function findWithDefaultTranslation($node_id)
    {
        $query = Kernel::getInstance()->em()
                        ->createQuery('
            SELECT n, ns FROM RZ\Renzo\Core\Entities\Node n 
            INNER JOIN n.nodeSources ns 
            INNER JOIN ns.translation t
            WHERE n.id = :node_id AND t.defaultTranslation = :defaultTranslation'
                        )->setParameter('node_id', (int)$node_id)
                        ->setParameter('defaultTranslation', true);

        try {
            return $query->getSingleResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }
}