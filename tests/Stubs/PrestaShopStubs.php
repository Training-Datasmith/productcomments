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

if (!defined('_PS_VERSION_')) {
    define('_PS_VERSION_', '1.7.8.0');
}
if (!defined('_DB_PREFIX_')) {
    define('_DB_PREFIX_', 'ps_');
}

class Configuration
{
    /** @var array<string, mixed> */
    private static $values = [];

    public static function reset()
    {
        self::$values = [];
    }

    public static function set($key, $value)
    {
        self::$values[$key] = $value;
    }

    public static function get($key)
    {
        return array_key_exists($key, self::$values) ? self::$values[$key] : null;
    }
}

class Tools
{
    /** @var array<string, mixed> */
    private static $values = [];

    public static function reset()
    {
        self::$values = [];
    }

    public static function setValue($key, $value)
    {
        self::$values[$key] = $value;
    }

    public static function getValue($key, $default = false)
    {
        return array_key_exists($key, self::$values) ? self::$values[$key] : $default;
    }
}

class Validate
{
    public static function isGenericName($name)
    {
        return empty($name) || preg_match('/^[^<>={}]*$/u', $name);
    }
}

class Hook
{
    /** @var array<int, array<string, mixed>> */
    public static $calls = [];

    public static function reset()
    {
        self::$calls = [];
    }

    public static function exec($hookName, $params = [])
    {
        self::$calls[] = ['hook' => $hookName, 'params' => $params];

        return true;
    }
}

class Module
{
    /** @var object */
    public $context;

    public function trans($id, $parameters = [], $domain = null, $locale = null)
    {
        return self::translate($id, $parameters);
    }

    public static function translate($id, $parameters)
    {
        if (!is_array($parameters)) {
            return $id;
        }
        $result = $id;
        if ($parameters === [] || array_keys($parameters) === range(0, count($parameters) - 1)) {
            foreach ($parameters as $value) {
                $result = preg_replace('/%s/', (string) $value, $result, 1);
            }

            return $result;
        }
        foreach ($parameters as $placeholder => $value) {
            $result = str_replace($placeholder, (string) $value, $result);
        }

        return $result;
    }
}

class ModuleFrontController
{
    /** @var object */
    public $context;

    /** @var object|null */
    public $container;

    /** @var mixed */
    public $ajaxRenderOutput;

    public function ajaxRender($value)
    {
        $this->ajaxRenderOutput = $value;
    }

    public function trans($id, $parameters = [], $domain = null, $locale = null)
    {
        return Module::translate($id, $parameters);
    }
}
