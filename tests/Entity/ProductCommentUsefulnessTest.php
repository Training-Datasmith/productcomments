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

use PrestaShop\Module\ProductComment\Entity\ProductComment;
use PrestaShop\Module\ProductComment\Entity\ProductCommentUsefulness;
use PrestaShop\Module\ProductComment\Tests\TestCase;

class ProductCommentUsefulnessTest extends TestCase
{
    public function testUsefulnessSetterAndCustomerId()
    {
        $comment = new ProductComment();
        $usefulness = new ProductCommentUsefulness($comment, 42, false);
        $this->assertFalse($usefulness->isUsefulness());
        $this->assertSame(42, $usefulness->getCustomerId());
        $this->assertSame($usefulness, $usefulness->setUsefulness(true));
        $this->assertTrue($usefulness->isUsefulness());
    }
}
