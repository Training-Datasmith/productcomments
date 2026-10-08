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

namespace PrestaShop\Module\ProductComment\Tests\Support;

use Doctrine\DBAL\Connection;

class DatabaseInstaller
{
    public static function install(Connection $connection)
    {
        foreach (self::coreStatements() as $sql) {
            $connection->executeUpdate($sql);
        }

        $connection->executeUpdate(
            'INSERT IGNORE INTO ps_lang (id_lang, iso_code, active) VALUES (1, \'en\', 1), (2, \'fr\', 1), (3, \'de\', 0)'
        );

        $installPath = dirname(__DIR__, 2) . '/install.sql';
        $sql = file_get_contents($installPath);
        $sql = str_replace(['PREFIX_', 'ENGINE_TYPE'], ['ps_', 'InnoDB'], $sql);
        foreach (preg_split('/;\s*[\r\n]+/', trim($sql)) as $query) {
            $query = trim($query);
            if ($query !== '') {
                $connection->executeUpdate($query);
            }
        }
    }

    /**
     * @return string[]
     */
    private static function coreStatements()
    {
        return [
            'CREATE TABLE IF NOT EXISTS ps_customer (
                id_customer INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                firstname VARCHAR(64) NOT NULL,
                lastname VARCHAR(64) NOT NULL,
                deleted TINYINT(1) NOT NULL DEFAULT 0
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            'CREATE TABLE IF NOT EXISTS ps_product (
                id_product INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            'CREATE TABLE IF NOT EXISTS ps_product_lang (
                id_product INT UNSIGNED NOT NULL,
                id_lang INT UNSIGNED NOT NULL,
                id_shop INT UNSIGNED NOT NULL,
                name VARCHAR(128) NOT NULL,
                PRIMARY KEY (id_product, id_lang, id_shop)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            'CREATE TABLE IF NOT EXISTS ps_lang (
                id_lang INT UNSIGNED NOT NULL PRIMARY KEY,
                iso_code VARCHAR(8) NOT NULL,
                active TINYINT(1) NOT NULL DEFAULT 1
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            'CREATE TABLE IF NOT EXISTS ps_category (
                id_category INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            'CREATE TABLE IF NOT EXISTS ps_category_product (
                id_category INT UNSIGNED NOT NULL,
                id_product INT UNSIGNED NOT NULL,
                PRIMARY KEY (id_category, id_product)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
        ];
    }
}
