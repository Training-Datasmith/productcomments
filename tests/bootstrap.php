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

require __DIR__ . '/Stubs/PrestaShopStubs.php';
require __DIR__ . '/vendor/autoload.php';

spl_autoload_register(function ($class) {
    if ($class === 'ProductComments') {
        require_once dirname(__DIR__) . '/productcomments.php';
    }
});
