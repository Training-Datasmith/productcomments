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

namespace PrestaShop\Module\ProductComment\Tests\Module;

use PrestaShop\Module\ProductComment\Tests\IntegrationTestCase;

class ProductCommentsModuleTest extends IntegrationTestCase
{
    public function testWidgetVariablesModerateOffIncludesUnvalidated()
    {
        \Configuration::set('PRODUCT_COMMENTS_MODERATE', '0');
        $productId = 800;
        $this->insertCommentRow(['id_product' => $productId, 'grade' => 5, 'validate' => 1]);
        $this->insertCommentRow(['id_product' => $productId, 'grade' => 1, 'validate' => 0]);

        $module = $this->createModule();
        $vars = $module->getWidgetVariables(null, ['id_product' => $productId]);

        $this->assertEqualsWithDelta(3.0, (float) $vars['average_grade'], 0.001);
        $this->assertSame(2, (int) $vars['nb_comments']);
        $this->assertArrayHasKey('post_allowed', $vars);
        $this->assertTrue($vars['post_allowed']);
    }

    public function testWidgetVariablesModerateOnKeepsValidatedOnly()
    {
        \Configuration::set('PRODUCT_COMMENTS_MODERATE', '1');
        $productId = 801;
        $this->insertCommentRow(['id_product' => $productId, 'grade' => 5, 'validate' => 1]);
        $this->insertCommentRow(['id_product' => $productId, 'grade' => 1, 'validate' => 0]);

        $module = $this->createModule();
        $vars = $module->getWidgetVariables(null, ['id_product' => $productId]);
        $this->assertEqualsWithDelta(5.0, (float) $vars['average_grade'], 0.001);
        $this->assertSame(1, (int) $vars['nb_comments']);
    }

    public function testRenderAuthorNameEscapesAndLinksCustomers()
    {
        $ref = new \ReflectionClass(\ProductComments::class);
        $instance = $ref->newInstanceWithoutConstructor();

        $linkCalls = [];
        $link = new class($linkCalls) {
            public $calls;

            public function __construct(&$calls)
            {
                $this->calls = &$calls;
            }

            public function getAdminLink($controller, $withToken, $params = [], $extra = [])
            {
                $merged = array_merge($params, $extra);
                $this->calls[] = [$controller, $merged];

                return 'http://admin/' . $controller;
            }
        };
        $instance->context = new \stdClass();
        $instance->context->link = $link;

        $html = $instance->renderAuthorName('Ann & Bob', ['customer_id' => 15]);
        $this->assertContains('http://admin/AdminCustomers', $html);
        $this->assertContains('Ann &amp; Bob', $html);
        $this->assertSame('AdminCustomers', $linkCalls[0][0]);
        $this->assertSame(15, $linkCalls[0][1]['id_customer']);

        $plain = $instance->renderAuthorName('Tom & Jerry', []);
        $this->assertSame('Tom &amp; Jerry', $plain);
        $this->assertNotContains('<a', $plain);
    }

    public function testStandardFieldListContract()
    {
        $ref = new \ReflectionClass(\ProductComments::class);
        $instance = $ref->newInstanceWithoutConstructor();
        $fields = $instance->getStandardFieldList();

        $expected = ['id_product_comment', 'title', 'content', 'grade', 'customer_name', 'name', 'date_add'];
        $this->assertSame($expected, array_keys($fields));
        $this->assertSame('/5', $fields['grade']['suffix']);
        $this->assertSame('renderAuthorName', $fields['customer_name']['callback']);
        $this->assertSame($instance, $fields['customer_name']['callback_object']);
    }

    private function createModule()
    {
        $repo = $this->createCommentRepository(true, 30);
        $module = new TestableProductCommentsModule($repo);
        $cookie = new \stdClass();
        $cookie->id_customer = 0;
        $cookie->id_guest = 0;
        $module->context = new \stdClass();
        $module->context->cookie = $cookie;

        return $module;
    }
}

class TestableProductCommentsModule extends \ProductComments
{
    private $repository;

    public function __construct($repository)
    {
        $this->repository = $repository;
    }

    public function get($serviceId)
    {
        if ($serviceId === 'product_comment_repository') {
            return $this->repository;
        }

        throw new \RuntimeException('Unknown service ' . $serviceId);
    }
}
