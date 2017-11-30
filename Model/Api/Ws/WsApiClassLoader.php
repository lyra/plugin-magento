<?php
/**
 * PayZen V2-Payment Module version 2.1.3 for Magento 2.x. Support contact : support@payzen.eu.
 *
 * NOTICE OF LICENSE
 *
 * This source file is licensed under the Open Software License version 3.0
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/osl-3.0.php
 *
 * @author    Lyra Network (http://www.lyra-network.com/)
 * @copyright 2014-2017 Lyra Network and contributors
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @category  payment
 * @package   payzen
 */

namespace Lyranetwork\Payzen\Model\Api\Ws;

class WsApiClassLoader
{
    private function __construct()
    {
        // do not instantiate this class
    }

    /**
     * Registers self::loadClass method as an autoloader.
     *
     * @param bool $prepend Whether to prepend the autoloader or not
     */
    public static function register($prepend = false)
    {
        spl_autoload_register(__NAMESPACE__ . '\WsApiClassLoader::loadClass', true, $prepend);
    }

    /**
     * Unregisters self::loadClass method as an autoloader.
     */
    public static function unregister()
    {
        spl_autoload_unregister(__NAMESPACE__ . '\WsApiClassLoader::loadClass');
    }

    public static function loadClass($class)
    {
        if (__NAMESPACE__ && strpos($class, __NAMESPACE__) === false) {
            return;
        }

        $pos = strrpos($class, '\\');
        if ($pos === false) {
            $pos = -1;
        }

        $file = __DIR__ . DIRECTORY_SEPARATOR . substr($class, $pos + 1) . '.php';
        if (is_file($file) && ($file !== __FILE__)) {
            include_once $file;
        }
    }
}
