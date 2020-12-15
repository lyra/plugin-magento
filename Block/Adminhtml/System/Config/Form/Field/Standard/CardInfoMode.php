<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento 2. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
namespace Lyranetwork\Payzen\Block\Adminhtml\System\Config\Form\Field\Standard;

/**
 * Custom renderer for the card info mode field.
 */

class CardInfoMode extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * Retrieve element HTML markup.
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        return parent::_getElementHtml($element) . "\n" . $this->renderScript($element);
    }

     /**
     * Render element JavaScript code.
     *
     * @return string
     */
    protected function renderScript(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $warningMessage = __('Warning, some payment means are not compatible with an integration by iframe. Please consult the documentation for more details.');

        $script = '
                <script>
                    require([
                        "prototype"
                    ], function () {
                        var element = $("' . $element->getId() . '");

                        Event.observe(element, "change", function() {
                            var cardDataMode = element.options[element.selectedIndex].value;

                            if (cardDataMode == 3) {
                                if (! confirm("' . $warningMessage . '")) {
                                    element.value = "' . $element->getValue(). '";
                                }
                            }
                        });
                   });
               </script>';

        return $script;
    }
}