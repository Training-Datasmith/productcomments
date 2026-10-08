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
class ListCommentsTest extends IntegrationTestCase
{
    public function testAnonymizeName()
    {
        $controller = new TestableListCommentsController();
        $this->assertSame('Jane D.', $controller->exposeAnonymize('Jane Doe'));
        $this->assertSame('Cher', $controller->exposeAnonymize('Cher'));
        $this->assertSame('José Á.', $controller->exposeAnonymize('José Álvarez'));
        $this->assertSame('Mary S.', $controller->exposeAnonymize('Mary Ann Smith'));
        $this->assertSame('Jane', $controller->exposeAnonymize('Jane '));
    }

    public function testAnonymisationOffKeepsFullNameWhenConfigIsStringZero()
    {
        \Configuration::set('PRODUCT_COMMENTS_ANONYMISATION', '0');
        \Configuration::set('PRODUCT_COMMENTS_MODERATE', '0');
        \Configuration::set('PRODUCT_COMMENTS_COMMENTS_PER_PAGE', 5);

        $productId = 600;
        $this->insertCommentRow([
            'id_product' => $productId,
            'id_customer' => 0,
            'customer_name' => 'Jane Doe',
            'validate' => 1,
        ]);

        $controller = $this->createListController();
        \Tools::setValue('id_product', $productId);
        \Tools::setValue('page', 1);
        $controller->display();

        $payload = json_decode($controller->ajaxRenderOutput, true);
        $this->assertSame('Jane Doe', $payload['comments'][0]['customer_name']);
    }

    public function testAnonymisationOnUsesLastInitial()
    {
        \Configuration::set('PRODUCT_COMMENTS_ANONYMISATION', '1');
        \Configuration::set('PRODUCT_COMMENTS_MODERATE', '0');
        \Configuration::set('PRODUCT_COMMENTS_COMMENTS_PER_PAGE', 5);

        $productId = 601;
        $this->insertCommentRow([
            'id_product' => $productId,
            'id_customer' => 0,
            'customer_name' => 'Jane Doe',
            'validate' => 1,
        ]);

        $controller = $this->createListController();
        \Tools::setValue('id_product', $productId);
        $controller->display();
        $payload = json_decode($controller->ajaxRenderOutput, true);
        $this->assertSame('Jane D.', $payload['comments'][0]['customer_name']);
        $this->assertNotEmpty($payload['comments'][0]['date_add']);
    }

    private function createListController()
    {
        $controller = new TestableListCommentsController();
        $repo = $this->createCommentRepository();
        $container = new ListCommentTestContainer($repo);
        $language = new \stdClass();
        $language->locale = 'en-US';
        $context = new \stdClass();
        $context->language = $language;
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

class TestableListCommentsController extends \ProductCommentsListCommentsModuleFrontController
{
    public function __construct()
    {
    }

    public function exposeAnonymize($name)
    {
        $method = new \ReflectionMethod($this, 'anonymizeName');
        $method->setAccessible(true);

        return $method->invoke($this, $name);
    }
}

class ListCommentTestContainer
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
