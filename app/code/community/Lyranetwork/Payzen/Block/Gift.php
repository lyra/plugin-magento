<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

class Lyranetwork_Payzen_Block_Gift extends Lyranetwork_Payzen_Block_Abstract
{
    protected $_model = 'gift';

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('payzen/gift.phtml');
    }

    public function getAvailableGcTypes()
    {
        return $this->_getModel()->getAvailableGcTypes();
    }

    public function getGcTypeImageSrc($card)
    {
        $card = strtolower($card);

        $path = Mage::getBaseDir('media') . DS . 'payzen' . DS . 'gift' . DS . $card . '.png';
        if ($this->_getHelper()->fileExists($path)) {
            return Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'payzen/gift/' . $card . '.png';
        } else {
            return false;
        }
    }
}
