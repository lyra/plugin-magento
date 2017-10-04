<?php
/**
 * PayZen V2-Payment Module version 2.1.2 for Magento 2.x. Support contact : support@payzen.eu.
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
namespace Lyranetwork\Payzen\Controller\Result;

class Redirect extends \Magento\Framework\Controller\Result\Redirect
{

    /**
     *
     * @var bool
     */
    protected $iframe;

    /**
     *
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * Constructor.
     *
     * @param \Magento\Framework\App\Response\RedirectInterface $redirect
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        \Magento\Framework\App\Response\RedirectInterface $redirect,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
    
        $this->resultPageFactory = $resultPageFactory;

        parent::__construct($redirect, $urlBuilder);
    }

    /**
     *
     * @param bool $iframe
     * @return $this
     */
    public function setIframe($iframe)
    {
        $this->iframe = $iframe;
        return $this;
    }

    /**
     *
     * {@inheritdoc}
     *
     */
    protected function render(\Magento\Framework\App\ResponseInterface $response)
    {
        if ($this->iframe) {
            $resultPage = $this->resultPageFactory->create();

            $block = $resultPage->getLayout()
                ->createBlock(\Lyranetwork\Payzen\Block\Payment\Iframe\Response::class)
                ->setTemplate('Lyranetwork_Payzen::payment/iframe/response.phtml')
                ->setForwardUrl($this->url);

            $response->setBody($block->toHtml());
            return $this;
        }

        return parent::render($response);
    }
}
