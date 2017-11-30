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

class Lyra_Payzen_Block_Field_Gift_UploadButton extends Mage_Core_Block_Abstract
{
    protected function _toHtml()
    {
        $html = '<div' . ($this->column['style'] ? ' style="' . $this->column['style'] . '"' : '') . '>';

        $html .= '<input type="file" name="'. $this->inputName . '" value="#{' . $this->columnName . '}"';
        $html .= ' class="' . ($this->column['class'] ? $this->column['class'] : 'input-text') . '"';
        if ($this->column['size']) {
            $html .= ' size="' . $this->column['size'] . '"';
        }

        $html .= '/>';

        $src = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'payzen/gift/#{logo}?'
            . Mage::getSingleton('core/date')->gmtTimestamp();
        $html .= '<img style="margin-left: 10px; vertical-align: middle; height: 18px;" alt="#{code}" src="'
            . $src . '" title="#{name}" >';

        $html .= '</div>';

        return $html;
    }
}
