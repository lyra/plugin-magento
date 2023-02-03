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

use Lyranetwork\Payzen\Model\Api\Form\Api as PayzenApi;

/**
 * Custom renderer for the module documentation URLs.
 */
class PluginDoc extends Label
{
    /**
     * @param \Lyranetwork\Payzen\Block\Adminhtml\System\Config\Form\Field\Context $context
     * @param array $data
     */
    public function __construct(
        \Lyranetwork\Payzen\Block\Adminhtml\System\Config\Form\Field\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Unset some non-related element parameters.
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        // Get documentation links.
        $languages = array(
            'fr' => 'Français',
            'en' => 'English',
            'es' => 'Español',
            'pt' => 'Português'
            // Complete when other languages are managed.
        );

        $docs = "";
        foreach (PayzenApi::getOnlineDocUri() as $lang => $docUri) {
            $docs .= '<a style="margin-left: 10px; text-decoration: none; text-transform: uppercase;" href="' . $docUri . 'magento2/sitemap.html" target="_blank">' . $languages[$lang] . '</a>';
        }

        $element->setComment($docs);

        return parent::render($element);
    }
}
