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
 * @file DataInheritanceEvent.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\Core\Events;

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\NodeType;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * DataInheritanceEvent
 */
class DataInheritanceEvent
{
    /**
     * @param Doctrine\ORM\Event\LoadClassMetadataEventArgs  $eventArgs
     */
    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs)
    {

        // the $metadata is all the mapping info for this class
        $metadata = $eventArgs->getClassMetadata();

        // the annotation reader accepts a ReflectionClass, which can be
        // obtained from the $metadata
        $class = $metadata->getReflectionClass();

        if ($class->getName() === 'RZ\Renzo\Core\Entities\NodesSources') {

            try {
                // List node types
                $nodeTypes = Kernel::getService('em')
                    ->getRepository('RZ\Renzo\Core\Entities\NodeType')
                    ->findAll();

                $map = array();
                foreach ($nodeTypes as $type) {
                    $map[strtolower($type->getName())] = NodeType::getGeneratedEntitiesNamespace().'\\'.$type->getSourceEntityClassName();
                }

                $metadata->setDiscriminatorMap($map);
            } catch (\Exception $e) {
                /*
                 * Database tables don't exist yet
                 * Need Install
                 */
                //$this->getSession()->getFlashBag()->add('error', 'Impossible to create discriminator map, make sure your database is fully installed.');
            }
        }
    }

    /**
     * Get NodesSources class metadata.
     *
     * @return Doctrine\ORM\Mapping\ClassMetadata
     */
    public static function getNodesSourcesMetadata()
    {
        $metadata = new ClassMetadata('RZ\Renzo\Core\Entities\NodesSources');
        $class = $metadata->getReflectionClass();

        try {
            /**
             *  List node types
             */
            $nodeTypes = Kernel::getService('em')
                ->getRepository('RZ\Renzo\Core\Entities\NodeType')
                ->findAll();

            $map = array();
            foreach ($nodeTypes as $type) {
                $map[strtolower($type->getName())] = NodeType::getGeneratedEntitiesNamespace().'\\'.$type->getSourceEntityClassName();
            }

            $metadata->setDiscriminatorMap($map);

            return $metadata;
        } catch (\PDOException $e) {
            /*
             * Database tables don't exist yet
             * Need Install
             */
            $this->getSession()->getFlashBag()->add('error', 'Impossible to create discriminator map, make sure your database is fully installed.');

            return null;
        }
    }


    /**
     * Check if given table exists.
     *
     * This method must be used at installation not to throw error when
     * creating discriminator map with node-types
     *
     * @param string  $table
     *
     * @return boolean
     */
    public function checkTable($table)
    {
        $conn = Kernel::getService('em')->getConnection();
        $sm = $conn->getSchemaManager();
        $tables = $sm->listTables();

        foreach ($tables as $table) {
            if ($table->getName() == $table) {
                return true;
            }
        }

        return false;
    }
}
