<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

class Lyranetwork_Payzen_Block_Iframe_Response extends Mage_Core_Block_Template
{
    /**
     * @var string
     */
    protected $_forwardUrl;

    /**
     * Set template for iframe response.
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('payzen/iframe/response.phtml');
    }

    /**
     * Set forward URL.
     *
     * @param  string $url
     * @return Lyranetwork_Payzen_Block_Iframe_Response
     */
    public function setForwardUrl($url)
    {
        $this->_forwardUrl = $url;
        return $this;
    }

    /**
     * Get forward URL.
     *
     * @return string
     */
    public function getForwardUrl()
    {
        return $this->_forwardUrl;
    }

    /**
     * Get loader HTML.
     *
     * @return string
     */
    public function getLoaderHtml()
    {
        $block = $this->getLayout()
            ->createBlock('core/template')
            ->setTemplate('payzen/iframe/loader.phtml');

        return $block->toHtml();
    }
}
