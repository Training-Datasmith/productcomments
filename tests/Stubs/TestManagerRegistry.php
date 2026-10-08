<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 */

namespace PrestaShop\Tests\Stubs;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

class TestManagerRegistry implements ManagerRegistry
{
    /** @var EntityManagerInterface */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getManager($name = null)
    {
        return $this->entityManager;
    }

    public function getManagers()
    {
        return ['default' => $this->entityManager];
    }

    public function resetManager($name = null)
    {
    }

    public function getManagerNames()
    {
        return ['default'];
    }

    public function getManagerForClass($class)
    {
        return $this->entityManager;
    }

    public function getDefaultManagerName()
    {
        return 'default';
    }

    public function getAliasNamespace($alias)
    {
        return $alias;
    }

    public function getRepository($persistentObject, $persistentManagerName = null)
    {
        return $this->entityManager->getRepository($persistentObject);
    }

    public function getDefaultConnectionName()
    {
        return 'default';
    }

    public function getConnection($name = null)
    {
        return $this->entityManager->getConnection();
    }

    public function getConnections()
    {
        return ['default' => $this->entityManager->getConnection()];
    }

    public function getConnectionNames()
    {
        return ['default'];
    }
}
