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

namespace PrestaShop\Module\ProductComment\Tests\Controller;

use PrestaShop\Module\ProductComment\Tests\IntegrationTestCase;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class UpdateCommentUsefulnessTest extends IntegrationTestCase
{
    public function testUsefulnessDisabledWhenConfigIsStringZero()
    {
        \Configuration::set('PRODUCT_COMMENTS_USEFULNESS', '0');
        $controller = $this->createController(1);
        \Tools::setValue('id_product_comment', 1);
        \Tools::setValue('usefulness', 1);
        $controller->display();
        $payload = json_decode($controller->ajaxRenderOutput, true);
        $this->assertFalse($payload['success']);
        $this->assertContains('not enabled', $payload['error']);
    }

    public function testVoteInsertThenUpdate()
    {
        \Configuration::set('PRODUCT_COMMENTS_USEFULNESS', '1');
        $commentId = $this->insertCommentRow(['title' => 'vote']);
        $controller = $this->createController(10);
        \Tools::setValue('id_product_comment', $commentId);
        \Tools::setValue('usefulness', 1);
        $controller->display();
        $first = json_decode($controller->ajaxRenderOutput, true);
        $this->assertTrue($first['success']);
        $this->assertSame(1, (int) $first['total_usefulness']);
        $this->assertSame(1, (int) $first['usefulness']);

        \Tools::setValue('usefulness', 0);
        $controller->display();
        $second = json_decode($controller->ajaxRenderOutput, true);
        $this->assertSame(1, (int) $second['total_usefulness']);
        $this->assertSame(0, (int) $second['usefulness']);

        $controllerOther = $this->createController(11);
        \Tools::setValue('usefulness', 1);
        $controllerOther->display();
        $third = json_decode($controllerOther->ajaxRenderOutput, true);
        $this->assertSame(2, (int) $third['total_usefulness']);
        $this->assertSame(1, (int) $third['usefulness']);
    }

    private function createController($customerId)
    {
        $controller = new TestableUpdateCommentUsefulnessController();
        $repo = $this->createCommentRepository();
        $container = new UsefulnessTestContainer(self::$entityManager, $repo);
        $cookie = new \stdClass();
        $cookie->id_customer = $customerId;
        $link = new class {
            public function getPageLink($page)
            {
                return 'http://shop/' . $page;
            }
        };
        $context = new \stdClass();
        $context->cookie = $cookie;
        $context->link = $link;
        $context->controller = new class($container) {
            private $container;

            public function __construct($container)
            {
                $this->container = $container;
            }

            public function getContainer()
            {
                return $this->container;
            }
        };
        $controller->context = $context;
        $controller->container = $container;

        return $controller;
    }
}

class TestableUpdateCommentUsefulnessController extends \ProductCommentsUpdateCommentUsefulnessModuleFrontController
{
    public function __construct()
    {
    }
}

class UsefulnessTestContainer
{
    private $entityManager;
    private $repository;

    public function __construct($entityManager, $repository)
    {
        $this->entityManager = $entityManager;
        $this->repository = $repository;
    }

    public function get($id)
    {
        if ($id === 'doctrine.orm.entity_manager') {
            return $this->entityManager;
        }
        if ($id === 'product_comment_repository') {
            return $this->repository;
        }

        throw new \RuntimeException('Unknown service ' . $id);
    }
}
