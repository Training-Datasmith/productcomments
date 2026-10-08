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
class CommentGradeTest extends IntegrationTestCase
{
    public function testCommentGradeModerateOffIncludesUnvalidated()
    {
        \Configuration::set('PRODUCT_COMMENTS_MODERATE', '0');
        $productId = 700;
        $this->insertCommentRow(['id_product' => $productId, 'grade' => 5, 'validate' => 1]);
        $this->insertCommentRow(['id_product' => $productId, 'grade' => 1, 'validate' => 0]);

        $controller = $this->createController();
        \Tools::setValue('id_products', [$productId]);
        $controller->display();
        $payload = json_decode($controller->ajaxRenderOutput, true);
        $this->assertSame(2, (int) $payload['products'][0]['comments_nb']);
        $this->assertEqualsWithDelta(3.0, (float) $payload['products'][0]['average_grade'], 0.001);
    }

    public function testCommentGradeModerateOnKeepsValidatedOnly()
    {
        \Configuration::set('PRODUCT_COMMENTS_MODERATE', '1');
        $productId = 701;
        $this->insertCommentRow(['id_product' => $productId, 'grade' => 5, 'validate' => 1]);
        $this->insertCommentRow(['id_product' => $productId, 'grade' => 1, 'validate' => 0]);

        $controller = $this->createController();
        \Tools::setValue('id_products', [$productId]);
        $controller->display();
        $payload = json_decode($controller->ajaxRenderOutput, true);
        $this->assertSame(1, (int) $payload['products'][0]['comments_nb']);
        $this->assertEqualsWithDelta(5.0, (float) $payload['products'][0]['average_grade'], 0.001);
    }

    public function testNonArrayProductIdsRendersNull()
    {
        $controller = $this->createController();
        \Tools::setValue('id_products', '1');
        $controller->display();
        $this->assertNull($controller->ajaxRenderOutput);
    }

    public function testEmptyProductIdListRendersNoProducts()
    {
        $controller = $this->createController();
        \Tools::setValue('id_products', []);
        $controller->display();
        $payload = json_decode($controller->ajaxRenderOutput, true);
        $this->assertSame([], $payload['products']);
    }

    private function createController()
    {
        $controller = new TestableCommentGradeController();
        $repo = $this->createCommentRepository();
        $container = new CommentGradeTestContainer($repo);
        $context = new \stdClass();
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

        return $controller;
    }
}

class TestableCommentGradeController extends \ProductCommentsCommentGradeModuleFrontController
{
    public function __construct()
    {
    }
}

class CommentGradeTestContainer
{
    private $repository;

    public function __construct($repository)
    {
        $this->repository = $repository;
    }

    public function get($id)
    {
        if ($id === 'product_comment_repository') {
            return $this->repository;
        }

        throw new \RuntimeException('Unknown service ' . $id);
    }
}
