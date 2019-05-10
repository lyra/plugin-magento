<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

class Lyra_Payzen_Block_Adminhtml_System_Config_Field_Gift_UploadButton extends Mage_Core_Block_Abstract
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
