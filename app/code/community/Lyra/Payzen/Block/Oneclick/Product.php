<?php
/**
 * PayZen V2-Payment Module version 1.8.0 for Magento 1.4-1.9. Support contact : support@payzen.eu.
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

class Lyra_Payzen_Block_Oneclick_Product extends Mage_Catalog_Block_Product_View
{
    public function isOneclickAvailable()
    {
        $model = Mage::getModel('payzen/payment_standard');

        // 1-Click is not available in current context
        $configContext = $model->getConfigData('one_click_location');
        if ($configContext != Lyra_Payzen_Helper_Payment::ONECLICK_LOCATION_PRODUCT
                && $configContext != Lyra_Payzen_Helper_Payment::ONECLICK_LOCATION_BOTH) {
            return false;
        }

        return $model->isOneclickAvailable();
    }

    public function getConfigData($name)
    {
        return Mage::getModel('payzen/payment_standard')->getConfigData($name);
    }
}
