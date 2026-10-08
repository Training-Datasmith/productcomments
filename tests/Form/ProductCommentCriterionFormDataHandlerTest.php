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

namespace PrestaShop\Module\ProductComment\Tests\Form;

use PrestaShop\Module\ProductComment\Entity\ProductCommentCriterion;
use PrestaShop\Module\ProductComment\Form\ProductCommentCriterionFormDataHandler;
use PrestaShop\Module\ProductComment\Tests\IntegrationTestCase;
use PrestaShopBundle\Entity\Repository\LangRepository;

class ProductCommentCriterionFormDataHandlerTest extends IntegrationTestCase
{
    public function testCreateLangsPersistsNames()
    {
        $handler = $this->createHandler();
        $criterion = new ProductCommentCriterion();
        $criterion->setType(1);
        $criterion->setActive(true);

        $handler->createLangs($criterion, [1 => 'Quality', 2 => 'Qualité']);

        $names = self::$connection->fetchAll(
            'SELECT id_lang, name FROM ps_product_comment_criterion_lang WHERE id_product_comment_criterion = ? ORDER BY id_lang',
            [$criterion->getId()]
        );
        $this->assertCount(2, $names);
        $this->assertSame('Quality', $names[0]['name']);
        $this->assertSame('Qualité', $names[1]['name']);
    }

    public function testUpdateLangsRenamesExisting()
    {
        $handler = $this->createHandler();
        $criterion = new ProductCommentCriterion();
        $criterion->setType(1);
        $criterion->setActive(true);
        $handler->createLangs($criterion, [1 => 'Old']);

        $handler->updateLangs($criterion, [1 => 'New']);

        $name = self::$connection->fetchColumn(
            'SELECT name FROM ps_product_comment_criterion_lang WHERE id_product_comment_criterion = ? AND id_lang = ?',
            [$criterion->getId(), 1]
        );
        $this->assertSame('New', $name);
    }

    private function createHandler()
    {
        return new ProductCommentCriterionFormDataHandler(
            $this->createCriterionRepository(),
            new LangRepository(self::$entityManager),
            self::$entityManager
        );
    }
}
