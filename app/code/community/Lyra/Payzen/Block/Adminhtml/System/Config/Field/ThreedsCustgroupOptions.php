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
 * Custom renderer for the customer group options field.
 */
class Lyra_Payzen_Block_Adminhtml_System_Config_Field_ThreedsCustgroupOptions
    extends Lyra_Payzen_Block_Adminhtml_System_Config_Field_CustgroupOptions
{
    public function __construct()
    {
        parent::__construct();

        // Maximum amount is not necessary for 3DS configuration.
        unset($this->_columns['amount_max']);
    }
}
