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

namespace PrestaShop\Module\ProductComment\Tests\Entity;

use PrestaShop\Module\ProductComment\Entity\ProductCommentCriterion;
use PrestaShop\Module\ProductComment\Entity\ProductCommentCriterionLang;
use PrestaShop\Module\ProductComment\Tests\TestCase;
use PrestaShopBundle\Entity\Lang;

class ProductCommentCriterionTest extends TestCase
{
    public function testScopeConstants()
    {
        $this->assertSame(1, ProductCommentCriterion::ENTIRE_CATALOG_TYPE);
        $this->assertSame(2, ProductCommentCriterion::CATEGORIES_TYPE);
        $this->assertSame(3, ProductCommentCriterion::PRODUCTS_TYPE);
        $this->assertSame(64, ProductCommentCriterion::NAME_MAX_LENGTH);
    }

    public function testCriterionNameIsEmptyThenFirstLang()
    {
        $criterion = new ProductCommentCriterion();
        $this->assertSame('', $criterion->getCriterionName());

        $lang1 = (new Lang())->setId(1)->setIsoCode('en');
        $lang2 = (new Lang())->setId(2)->setIsoCode('fr');
        $criterionLang1 = (new ProductCommentCriterionLang())->setLang($lang1)->setName('First');
        $criterionLang2 = (new ProductCommentCriterionLang())->setLang($lang2)->setName('Second');
        $criterion->addCriterionLang($criterionLang1);
        $criterion->addCriterionLang($criterionLang2);

        $this->assertSame('First', $criterion->getCriterionName());
        $this->assertSame($criterion, $criterionLang1->getProductCommentCriterion());
    }

    public function testLangLookupById()
    {
        $criterion = new ProductCommentCriterion();
        $lang = (new Lang())->setId(2)->setIsoCode('fr');
        $criterionLang = (new ProductCommentCriterionLang())->setLang($lang)->setName('Qualité');
        $criterion->addCriterionLang($criterionLang);

        $this->assertSame($criterionLang, $criterion->getCriterionLangByLangId(2));
        $this->assertNull($criterion->getCriterionLangByLangId(99));
    }

    public function testIsValidRejectsGenericNamePunctuation()
    {
        $criterion = new ProductCommentCriterion();
        $lang = (new Lang())->setId(1)->setIsoCode('en');

        $bad = (new ProductCommentCriterionLang())->setLang($lang)->setName('a<b');
        $criterion->addCriterionLang($bad);
        $this->assertFalse($criterion->isValid());

        $criterion = new ProductCommentCriterion();
        $bad2 = (new ProductCommentCriterionLang())->setLang($lang)->setName('x{y');
        $criterion->addCriterionLang($bad2);
        $this->assertFalse($criterion->isValid());

        $criterion = new ProductCommentCriterion();
        $good = (new ProductCommentCriterionLang())->setLang($lang)->setName('Quality');
        $criterion->addCriterionLang($good);
        $this->assertTrue($criterion->isValid());

        $criterion = new ProductCommentCriterion();
        $accent = (new ProductCommentCriterionLang())->setLang($lang)->setName('Qualité');
        $criterion->addCriterionLang($accent);
        $this->assertTrue($criterion->isValid());
    }
}
