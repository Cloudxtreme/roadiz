<?php 

namespace RZ\Renzo\Core\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use RZ\Renzo\Core\AbstractEntities\PersistableObject;

/**
 * @Entity
 * @Table(name="permissions")
 */
class Permission extends PersistableObject
{
	
	public function __construct()
    {
    	parent::__construct();
    }
}