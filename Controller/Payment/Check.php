<?php
/**
 * PayZen V2-Payment Module version 2.1.1 for Magento 2.x. Support contact : support@payzen.eu.
 *
 * NOTICE OF LICENSE
 *
 * This source file is licensed under the Open Software License version 3.0
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category  payment
 * @package   payzen
 * @author    Lyra Network (http://www.lyra-network.com/)
 * @copyright 2014-2016 Lyra Network and contributors
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
namespace Lyranetwork\Payzen\Controller\Payment;

class Check extends \Magento\Framework\App\Action\Action implements \Lyranetwork\Payzen\Api\CheckActionInterface
{
    /**
     * @var \Lyranetwork\Payzen\Controller\Processor\CheckProcessor
     */
    private $checkProcessor;

    /**
     * @var \Magento\Framework\Controller\Result\RawFactory
     */
    private $rawResultFactory;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Lyranetwork\Payzen\Controller\Processor\CheckProcessor $checkProcessor
     * @param \Magento\Framework\Controller\Result\RawFactory $rawResultFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Lyranetwork\Payzen\Controller\Processor\CheckProcessor $checkProcessor,
        \Magento\Framework\Controller\Result\RawFactory $rawResultFactory
    ) {
        $this->checkProcessor = $checkProcessor;
        $this->rawResultFactory = $rawResultFactory;

        parent::__construct($context);
    }

    public function execute()
    {
        return $this->checkProcessor->execute($this);
    }

    public function renderResponse($text)
    {
        $rawResult = $this->rawResultFactory->create();
        $rawResult->setContents($text);

        return $rawResult;
    }
}
