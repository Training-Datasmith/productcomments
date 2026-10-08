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

namespace PrestaShop\Module\ProductComment\Tests;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Doctrine\ORM\Tools\Setup;
use PrestaShop\Module\ProductComment\Repository\ProductCommentCriterionRepository;
use PrestaShop\Module\ProductComment\Repository\ProductCommentRepository;
use PrestaShop\Module\ProductComment\Tests\Support\DatabaseInstaller;
use PrestaShop\Module\ProductComment\Tests\Support\TablePrefixSubscriber;
use PrestaShop\Tests\Stubs\TestManagerRegistry;

abstract class IntegrationTestCase extends TestCase
{
    /** @var EntityManager|null */
    protected static $entityManager;

    /** @var \Doctrine\DBAL\Connection|null */
    protected static $connection;

    /** @var TestManagerRegistry|null */
    protected static $registry;

    /** @var bool */
    private static $schemaInstalled = false;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        $host = getenv('PC_DB_HOST');
        if ($host === false || $host === '') {
            throw new \RuntimeException('PC_DB_HOST must be set for integration tests');
        }

        $password = getenv('PC_DB_PASSWORD') ?: 'productcomments';

        $eventManager = new EventManager();
        $eventManager->addEventSubscriber(new TablePrefixSubscriber('ps_'));

        self::$connection = DriverManager::getConnection([
            'driver' => 'pdo_mysql',
            'host' => $host,
            'dbname' => 'productcomments',
            'user' => 'root',
            'password' => $password,
            'charset' => 'utf8mb4',
        ], null, $eventManager);

        $paths = [
            dirname(__DIR__) . '/src/Entity',
            __DIR__ . '/Stubs/PrestaShopBundle/Entity',
        ];
        $config = Setup::createAnnotationMetadataConfiguration($paths, true, null, null, false);
        $config->setNamingStrategy(new UnderscoreNamingStrategy());
        $config->addEntityNamespace('PrestaShopBundle', 'PrestaShopBundle\\Entity');

        self::$entityManager = EntityManager::create(self::$connection, $config, $eventManager);
        self::$registry = new TestManagerRegistry(self::$entityManager);

        if (!self::$schemaInstalled) {
            DatabaseInstaller::install(self::$connection);
            self::$schemaInstalled = true;
        }
    }

    protected function setUp()
    {
        parent::setUp();
        self::$connection->beginTransaction();
    }

    protected function tearDown()
    {
        if (self::$connection->isTransactionActive()) {
            self::$connection->rollBack();
        }
        self::$entityManager->clear();
        parent::tearDown();
    }

    /**
     * @return ProductCommentRepository
     */
    protected function createCommentRepository($guestAllowed = true, $minimalTime = 30)
    {
        return new ProductCommentRepository(
            self::$registry,
            self::$connection,
            'ps_',
            $guestAllowed,
            $minimalTime
        );
    }

    /**
     * @return ProductCommentCriterionRepository
     */
    protected function createCriterionRepository()
    {
        return new ProductCommentCriterionRepository(
            self::$registry,
            self::$connection,
            'ps_'
        );
    }

    protected function insertProduct($id, $langId, $shopId, $name)
    {
        self::$connection->executeUpdate(
            'INSERT INTO ps_product (id_product) VALUES (?) ON DUPLICATE KEY UPDATE id_product = id_product',
            [$id]
        );
        self::$connection->executeUpdate(
            'INSERT INTO ps_product_lang (id_product, id_lang, id_shop, name) VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE name = VALUES(name)',
            [$id, $langId, $shopId, $name]
        );
    }

    protected function insertCustomer($id, $firstname, $lastname, $deleted = 0)
    {
        self::$connection->executeUpdate(
            'INSERT INTO ps_customer (id_customer, firstname, lastname, deleted) VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE firstname = VALUES(firstname), lastname = VALUES(lastname), deleted = VALUES(deleted)',
            [$id, $firstname, $lastname, $deleted]
        );
    }

    /**
     * @return int
     */
    protected function insertCommentRow(array $row)
    {
        $defaults = [
            'id_product' => 1,
            'id_customer' => 0,
            'id_guest' => 0,
            'title' => 'Title',
            'content' => 'Content',
            'customer_name' => 'Guest',
            'grade' => 5,
            'validate' => 1,
            'deleted' => 0,
            'date_add' => '2020-01-01 00:00:00',
        ];
        $data = array_merge($defaults, $row);

        self::$connection->executeUpdate(
            'INSERT INTO ps_product_comment
            (id_product, id_customer, id_guest, title, content, customer_name, grade, validate, deleted, date_add)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $data['id_product'],
                $data['id_customer'],
                $data['id_guest'],
                $data['title'],
                $data['content'],
                $data['customer_name'],
                $data['grade'],
                $data['validate'],
                $data['deleted'],
                $data['date_add'],
            ]
        );

        return (int) self::$connection->lastInsertId();
    }
}
