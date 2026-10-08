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

class ProductCommentCriterionLangTest extends TestCase
{
    public function testSettersRoundTrip()
    {
        $criterion = new ProductCommentCriterion();
        $lang = (new Lang())->setId(1)->setIsoCode('en');
        $criterionLang = new ProductCommentCriterionLang();
        $this->assertSame($criterionLang, $criterionLang->setName('Quality'));
        $this->assertSame('Quality', $criterionLang->getName());
        $this->assertSame($criterionLang, $criterionLang->setLang($lang));
        $this->assertSame($lang, $criterionLang->getLang());
        $this->assertSame($criterionLang, $criterionLang->setProductCommentCriterion($criterion));
        $this->assertSame($criterion, $criterionLang->getProductCommentCriterion());
    }
}
