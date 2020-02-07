<?php

/**
 * This file is part of the EcommitUtilBundle package.
 *
 * (c) E-commit <contact@e-commit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ecommit\UtilBundle\Doctrine;

use Doctrine\Persistence\ManagerRegistry;

class ClearEntityManager
{
    /**
     * @var ManagerRegistry
     */
    protected $doctrine;

    /**
     * @var array
     */
    protected $snapshots = array();

    public function __construct(ManagerRegistry $registry = null)
    {
        $this->doctrine = $registry;
    }

    /**
     * EntityManager snapshot
     * @param string|null $managerName
     */
    public function snapshotEntityManager($managerName = null)
    {
        if (null === $this->doctrine) {
            throw new \Exception('Doctrine is required');
        }

        if (null === $managerName) {
            $managerName = $this->doctrine->getDefaultConnectionName();
        }
        $this->snapshots[$managerName] = $this->doctrine->getManager($managerName)->getUnitOfWork()->getIdentityMap();
    }

    /**
     * Detach all objects in EntityManager persisted since snapshot
     * @param string|null $managerName
     */
    public function clearEntityManager($managerName = null)
    {
        if (null === $this->doctrine) {
            throw new \Exception('Doctrine is required');
        }

        if (null === $managerName) {
            $managerName = $this->doctrine->getDefaultConnectionName();
        }
        if (!array_key_exists($managerName, $this->snapshots)) {
            throw new \Exception('The snapshot was not done');
        }
        $snapshot = $this->snapshots[$managerName];

        $em = $this->doctrine->getManager($managerName);
        $identityMap = $em->getUnitOfWork()->getIdentityMap();
        foreach ($identityMap as $class => $objects) {
            foreach ($objects as $id => $object) {
                if (!array_key_exists($class, $snapshot) || !array_key_exists($id, $snapshot[$class])) {
                    $em->detach($object);
                }
            }
        }
    }
}
