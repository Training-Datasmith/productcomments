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
use PrestaShop\Module\ProductComment\Entity\ProductCommentCriterionLang;
use PrestaShop\Module\ProductComment\Form\ProductCommentCriterionFormDataProvider;
use PrestaShop\Module\ProductComment\Tests\IntegrationTestCase;
use PrestaShopBundle\Entity\Lang;
use PrestaShopBundle\Entity\Repository\LangRepository;

class ProductCommentCriterionFormDataProviderTest extends IntegrationTestCase
{
    public function testGetDataMapsTypeActiveAndNames()
    {
        $criterion = new ProductCommentCriterion();
        $criterion->setType(2);
        $criterion->setActive(true);
        $lang = self::$entityManager->find(Lang::class, 1);
        $criterionLang = new ProductCommentCriterionLang();
        $criterionLang->setLang($lang)->setName('Fit');
        $criterion->addCriterionLang($criterionLang);
        self::$entityManager->persist($criterion);
        self::$entityManager->flush();

        $provider = new ProductCommentCriterionFormDataProvider(
            $this->createCriterionRepository(),
            new LangRepository(self::$entityManager)
        );
        $data = $provider->getData($criterion->getId());

        $this->assertSame(2, $data['type']);
        $this->assertTrue($data['active']);
        $this->assertSame('Fit', $data['name'][1]);
    }

    public function testGetDefaultDataIncludesActiveLanguageKeysOnly()
    {
        $provider = new ProductCommentCriterionFormDataProvider(
            $this->createCriterionRepository(),
            new LangRepository(self::$entityManager)
        );
        $data = $provider->getDefaultData();

        $this->assertSame('', $data['type']);
        $this->assertFalse($data['active']);
        $this->assertArrayHasKey(1, $data['name']);
        $this->assertArrayHasKey(2, $data['name']);
        $this->assertArrayNotHasKey(3, $data['name']);
    }
}
