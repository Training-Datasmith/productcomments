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

use PrestaShop\Module\ProductComment\Entity\ProductComment;
use PrestaShop\Module\ProductComment\Entity\ProductCommentCriterion;
use PrestaShop\Module\ProductComment\Entity\ProductCommentCriterionLang;
use PrestaShop\Module\ProductComment\Tests\IntegrationTestCase;
use PrestaShopBundle\Entity\Lang;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class PostCommentTest extends IntegrationTestCase
{
    public function testValidateCommentTitleAndGuestNameBoundaries()
    {
        $controller = new TestablePostCommentController();

        $comment = new ProductComment();
        $comment->setTitle('');
        $errors = $controller->callValidateComment($comment);
        $this->assertContains('Title cannot be empty', $errors);

        $comment->setTitle(str_repeat('a', 64));
        $errors = $controller->callValidateComment($comment);
        $this->assertNotContains('Title cannot be more than 64 characters', $errors);

        $comment->setTitle(str_repeat('a', 65));
        $errors = $controller->callValidateComment($comment);
        $this->assertContains('Title cannot be more than 64 characters', $errors);

        $comment = new ProductComment();
        $comment->setTitle('Ok');
        $comment->setCustomerId(0);
        $comment->setCustomerName('');
        $errors = $controller->callValidateComment($comment);
        $this->assertContains('Customer name cannot be empty', $errors);

        $comment->setCustomerName(str_repeat('b', 65));
        $errors = $controller->callValidateComment($comment);
        $this->assertContains('Customer name cannot be more than 64 characters', $errors);

        $comment->setCustomerName(str_repeat('b', 64));
        $errors = $controller->callValidateComment($comment);
        $this->assertNotContains('Customer name cannot be more than 64 characters', $errors);

        $comment->setCustomerId(5);
        $comment->setCustomerName('');
        $errors = $controller->callValidateComment($comment);
        $this->assertNotContains('Customer name cannot be empty', $errors);
    }

    public function testAddCommentGradesAveragesAndPersists()
    {
        $controller = new TestablePostCommentController();
        $controller->setContainer(self::$entityManager);

        $criterion = new ProductCommentCriterion();
        $criterion->setType(1);
        $criterion->setActive(true);
        $lang = self::$entityManager->find(Lang::class, 1);
        $criterionLang = new ProductCommentCriterionLang();
        $criterionLang->setLang($lang)->setName('One');
        $criterion->addCriterionLang($criterionLang);
        self::$entityManager->persist($criterion);
        self::$entityManager->flush();

        $comment = new ProductComment();
        $comment->setProductId(1);
        $comment->setTitle('T');
        $comment->setContent('C');
        $comment->setCustomerId(1);
        $comment->setGrade(0);
        $comment->setDateAdd(new \DateTime('2020-01-01', new \DateTimeZone('UTC')));
        self::$entityManager->persist($comment);
        self::$entityManager->flush();
        $controller->callAddCommentGrades($comment, [$criterion->getId() => 5]);
        $this->assertSame(5, $comment->getGrade());

        $comment2 = new ProductComment();
        $comment2->setProductId(1);
        $comment2->setTitle('T2');
        $comment2->setContent('C2');
        $comment2->setCustomerId(1);
        $comment2->setGrade(0);
        $comment2->setDateAdd(new \DateTime('2020-01-02', new \DateTimeZone('UTC')));
        self::$entityManager->persist($comment2);
        self::$entityManager->flush();
        $criterion2 = new ProductCommentCriterion();
        $criterion2->setType(1);
        $criterion2->setActive(true);
        $langRow = new ProductCommentCriterionLang();
        $langRow->setLang($lang)->setName('Two');
        $criterion2->addCriterionLang($langRow);
        self::$entityManager->persist($criterion2);
        self::$entityManager->flush();

        $controller->callAddCommentGrades($comment2, [
            $criterion->getId() => 5,
            $criterion2->getId() => 4,
        ]);
        $this->assertEqualsWithDelta(4.5, $comment2->getGrade(), 0.001);

        self::$entityManager->flush();
        $count = (int) self::$connection->fetchColumn(
            'SELECT COUNT(*) FROM ps_product_comment_grade WHERE id_product_comment = ?',
            [$comment2->getId()]
        );
        $this->assertSame(2, $count);
    }

    public function testGuestIsRejectedWhenAllowGuestsIsStringZero()
    {
        \Configuration::set('PRODUCT_COMMENTS_ALLOW_GUESTS', '0');
        $controller = $this->createDisplayController(false);
        $controller->display();
        $payload = json_decode($controller->ajaxRenderOutput, true);
        $this->assertFalse($payload['success']);
        $this->assertContains('logged in', $payload['error']);
    }

    public function testAllowedGuestReachesTitleValidation()
    {
        \Configuration::set('PRODUCT_COMMENTS_ALLOW_GUESTS', '1');
        $controller = $this->createDisplayController(false);
        \Tools::setValue('id_product', 1);
        \Tools::setValue('comment_title', '');
        \Tools::setValue('comment_content', 'body');
        \Tools::setValue('customer_name', 'Guest');
        \Tools::setValue('criterion', []);
        $controller->display();
        $payload = json_decode($controller->ajaxRenderOutput, true);
        $this->assertFalse($payload['success']);
        $this->assertArrayNotHasKey('error', $payload);
        $this->assertContains('Title cannot be empty', $payload['errors']);
    }

    private function createDisplayController($loggedIn)
    {
        $controller = new TestablePostCommentController();
        $customer = new class($loggedIn) {
            private $logged;

            public function __construct($logged)
            {
                $this->logged = $logged;
            }

            public function isLogged()
            {
                return $this->logged;
            }
        };
        $cookie = new \stdClass();
        $cookie->id_customer = $loggedIn ? 1 : 0;
        $cookie->id_guest = 2;
        $link = new class {
            public function getPageLink($page)
            {
                return 'http://shop/' . $page;
            }
        };
        $repo = $this->createCommentRepository(true, 30);
        $container = new TestContainer(self::$entityManager, $repo);
        $context = new \stdClass();
        $context->customer = $customer;
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

class TestablePostCommentController extends \ProductCommentsPostCommentModuleFrontController
{
    public function __construct()
    {
    }

    public function setContainer($entityManager)
    {
        $this->container = new TestContainer($entityManager, null);
    }

    public function callValidateComment(ProductComment $comment)
    {
        $method = new \ReflectionMethod($this, 'validateComment');
        $method->setAccessible(true);

        return $method->invoke($this, $comment);
    }

    public function callAddCommentGrades(ProductComment $comment, array $criterions)
    {
        $method = new \ReflectionMethod($this, 'addCommentGrades');
        $method->setAccessible(true);
        $method->invoke($this, $comment, $criterions);
    }
}

class TestContainer
{
    private $entityManager;
    private $commentRepository;

    public function __construct($entityManager, $commentRepository)
    {
        $this->entityManager = $entityManager;
        $this->commentRepository = $commentRepository;
    }

    public function get($id)
    {
        if ($id === 'doctrine.orm.entity_manager') {
            return $this->entityManager;
        }
        if ($id === 'product_comment_repository') {
            return $this->commentRepository;
        }

        throw new \RuntimeException('Unknown service ' . $id);
    }
}
