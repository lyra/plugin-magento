<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento 2. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
namespace Lyranetwork\Payzen\Block\Adminhtml\System\Config\Fieldset;

/**
 * Fieldset renderer which depends on features enabled.
 */
class Dependant extends \Magento\Config\Block\System\Config\Form\Fieldset
{
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        // Get configured features.
        $features = \Lyranetwork\Payzen\Helper\Data::$pluginFeatures;

        $data = $element->getOriginalData();
        if (isset($data['feature']) && key_exists($data['feature'], $features) && ! $features[$data['feature']]) {
            return '';
        }

        if (isset($data['feature']) && ($data['feature'] === 'multi')) {
            $comment = '';
            if ($features['restrictmulti']) {
                $comment = '<p style="background: none repeat scroll 0 0 #FFFFE0; border: 1px solid #E6DB55; margin: 0 0 20px; padding: 10px;">';
                $comment .= $element->getComment();
                $comment .= '</p>';
            }

            $element->setComment($comment);
        }

        return parent::render($element);
    }
}
