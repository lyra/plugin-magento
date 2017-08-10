<?php
/**
 * PayZen V2-Payment Module version 2.1.1 for Magento 2.x. Support contact : support@payzen.eu.
 *
 * NOTICE OF LICENSE
 *
 * This source file is licensed under the Open Software License version 3.0
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category  payment
 * @package   payzen
 * @author    Lyra Network (http://www.lyra-network.com/)
 * @copyright 2014-2016 Lyra Network and contributors
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
namespace Lyranetwork\Payzen\Model\System\Config\Backend\Standard;

class CardInfoModes extends \Magento\Framework\App\Config\Value
{
    private $error = false;

    public function save()
    {
        $value = $this->getValue();

        if ($value == 3 && !$this->isFrontSecure()) {
            $this->error = true;
            $this->setValue(1);
        }

        return parent::save();
    }

    public function afterCommitCallback()
    {
        if ($this->error) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('The card data entry on merchant site cannot be used without enabling SSL.')
            );
        }

        return parent::afterCommitCallback();
    }

    private function isFrontSecure()
    {
        return $this->_config->isSetFlag(
            \Magento\Store\Model\Store::XML_PATH_SECURE_IN_FRONTEND,
            $this->getScope(),
            $this->getScopeId()
        );
    }
}
