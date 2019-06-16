<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento 2. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
namespace Lyranetwork\Payzen\Block\Adminhtml\System\Config\Form\Field;

/**
 * Custom renderer for the init button.
 */
class InitButton extends \Magento\Config\Block\System\Config\Form\Field
{

    /**
     * Set template to itself.
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();

        if (! $this->getTemplate()) {
            $this->setTemplate('Lyranetwork_Payzen::system/config/form/field/init_button.phtml');
        }

        return $this;
    }

    /**
     * Unset some non-related element parameters.
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $element->unsScope()
            ->unsCanUseWebsiteValue()
            ->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * Get the button and scripts contents.
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $fieldConfig = $element->getFieldConfig();

        $this->addData(
            [
                'button_label' => __($fieldConfig['button_label']),
                'button_url' => $this->getUrl(
                    $fieldConfig['button_url'],
                    [
                        '_nosid' => true
                    ]
                ),
                'html_id' => $element->getHtmlId()
            ]
        );

        return $this->_toHtml();
    }
}
