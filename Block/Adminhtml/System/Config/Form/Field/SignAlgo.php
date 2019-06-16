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
 * Custom renderer for the signature algorithm selector.
 */
class SignAlgo extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * Update comment.
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        // Get configured features.
        $features = \Lyranetwork\Payzen\Helper\Data::$pluginFeatures;
        if ($features['shatwo']) {
            $comment = preg_replace('#<br /><b>[^<>]+</b>#', '', $element->getComment());
            $element->setComment($comment);
        }

        return parent::render($element);
    }
}
