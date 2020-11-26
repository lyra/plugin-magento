<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento 2. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
namespace Lyranetwork\Payzen\Block\Payment\Iframe;

class Response extends \Magento\Framework\View\Element\Template
{
    /**
     * @var string
     */
    protected $forwardUrl;

    /**
     * Set forward path.
     *
     * @param string $path
     * @param string $params
     * @return \Lyranetwork\Payzen\Block\Payment\Iframe\Response
     */
    public function setForwardPath($path, $params = [])
    {
        $this->forwardUrl = $this->_urlBuilder->getUrl($path, $params);
        return $this;
    }

    /**
     * Set forward URL.
     *
     * @param string $url
     * @return \Lyranetwork\Payzen\Block\Payment\Iframe\Response
     */
    public function setForwardUrl($url)
    {
        $this->forwardUrl = $url;
        return $this;
    }

    /**
     * Get forward URL.
     *
     * @return string
     */
    public function getForwardUrl()
    {
        return $this->forwardUrl;
    }

    /**
     * Get loader HTML.
     *
     * @return string
     */
    public function getLoaderHtml()
    {
        $block = $this->getLayout()
            ->createBlock(\Magento\Framework\View\Element\Template::class)
            ->setTemplate('Lyranetwork_Payzen::payment/iframe/loader.phtml');

        return $block->toHtml();
    }
}
