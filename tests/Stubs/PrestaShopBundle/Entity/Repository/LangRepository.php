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

namespace PrestaShopBundle\Entity\Repository;

use Doctrine\ORM\EntityManagerInterface;
use PrestaShopBundle\Entity\Lang;

class LangRepository
{
    /** @var EntityManagerInterface */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function find($id)
    {
        return $this->entityManager->find(Lang::class, $id);
    }

    /**
     * @param array<string, mixed> $criteria
     *
     * @return Lang[]
     */
    public function findBy(array $criteria)
    {
        return $this->entityManager->getRepository(Lang::class)->findBy($criteria);
    }
}
