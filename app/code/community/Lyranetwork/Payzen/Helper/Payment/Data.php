<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

class Lyranetwork_Payzen_Helper_Payment_Data extends Mage_Payment_Helper_Data
{
    /**
     * Retrieve method model object.
     *
     * @param   string $code
     * @return  Mage_Payment_Model_Method_Abstract|false
     */
    public function getMethodInstance($code)
    {
        if (preg_match("/payzen_other_/", $code)) {
            $code = 'payzen_other';
        }

        $key = self::XML_PATH_PAYMENT_METHODS . '/' . $code . '/model';
        $class = Mage::getStoreConfig($key);
        return Mage::getModel($class);
    }
}
