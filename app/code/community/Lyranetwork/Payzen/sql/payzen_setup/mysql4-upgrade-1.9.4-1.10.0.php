<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

/**
 * For backward compatibility (less than 1.6 Magento versions).
 */

$io = new Varien_Io_File();
$upgradeFile = __DIR__ . DS . 'upgrade-1.9.4-1.10.0.php';
if ($io->fileExists($upgradeFile)) {
    include_once $upgradeFile;
}
