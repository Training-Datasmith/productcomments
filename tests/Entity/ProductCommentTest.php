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
use PrestaShop\Module\ProductComment\Tests\TestCase;

class ProductCommentTest extends TestCase
{
    public function testDefaultsAreUnvalidatedAndNotDeleted()
    {
        $comment = new ProductComment();
        $this->assertFalse($comment->isValidate());
        $this->assertFalse($comment->isDeleted());
    }

    public function testSettersRoundTripAndReturnSameInstance()
    {
        $comment = new ProductComment();
        $this->assertSame($comment, $comment->setProductId(3));
        $this->assertSame(3, $comment->getProductId());
        $this->assertSame($comment, $comment->setCustomerId(4));
        $this->assertSame(4, $comment->getCustomerId());
        $this->assertSame($comment, $comment->setGuestId(5));
        $this->assertSame(5, $comment->getGuestId());
        $this->assertSame($comment, $comment->setCustomerName('Ann'));
        $this->assertSame('Ann', $comment->getCustomerName());
        $this->assertSame($comment, $comment->setTitle('Nice'));
        $this->assertSame('Nice', $comment->getTitle());
        $this->assertSame($comment, $comment->setContent('Body'));
        $this->assertSame('Body', $comment->getContent());
        $this->assertSame($comment, $comment->setGrade(4));
        $this->assertSame(4, $comment->getGrade());
        $this->assertSame($comment, $comment->setValidate(true));
        $this->assertTrue($comment->isValidate());
        $this->assertSame($comment, $comment->setDeleted(true));
        $this->assertTrue($comment->isDeleted());
    }

    public function testDateAddRoundTrip()
    {
        $comment = new ProductComment();
        $date = new \DateTime('2020-05-01 08:09:10', new \DateTimeZone('UTC'));
        $comment->setDateAdd($date);
        $this->assertSame($date, $comment->getDateAdd());
    }

    public function testToArrayMapsFieldsAndAtomDate()
    {
        $comment = new ProductComment();
        $comment->setProductId(7);
        $comment->setTitle('T');
        $comment->setContent('C');
        $comment->setCustomerName('N');
        $comment->setGrade(5);
        $comment->setDateAdd(new \DateTime('2020-05-01 08:09:10', new \DateTimeZone('UTC')));

        $reflection = new \ReflectionClass($comment);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($comment, 12);

        $array = $comment->toArray();
        $this->assertSame(7, $array['id_product']);
        $this->assertSame(12, $array['id_product_comment']);
        $this->assertSame('T', $array['title']);
        $this->assertSame('C', $array['content']);
        $this->assertSame('N', $array['customer_name']);
        $this->assertSame(5, $array['grade']);
        $this->assertSame('2020-05-01T08:09:10+00:00', $array['date_add']);
    }
}
