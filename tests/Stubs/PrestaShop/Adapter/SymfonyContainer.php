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

namespace PrestaShop\PrestaShop\Adapter;

class SymfonyContainer
{
    public static function getInstance()
    {
        return new self();
    }

    public function get($id)
    {
        if ($id === 'translator') {
            return new class {
                public function trans($id, $parameters = [], $domain = null)
                {
                    return $id;
                }
            };
        }

        throw new \RuntimeException('Unknown service: ' . $id);
    }
}
