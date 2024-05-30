<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento 2. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
namespace Lyranetwork\Payzen\Controller\Adminhtml\Payment;

use Lyranetwork\Payzen\Model\ResponseException;

class Check extends \Magento\Backend\App\Action
{
    /**
     * @var \Lyranetwork\Payzen\Controller\Processor\CheckProcessor
     */
    protected $checkProcessor;

    /**
     * @var \Magento\Framework\Controller\Result\RawFactory
     */
    protected $rawResultFactory;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Lyranetwork\Payzen\Controller\Processor\CheckProcessor $checkProcessor
     * @param \Magento\Framework\Controller\Result\RawFactory $rawResultFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Lyranetwork\Payzen\Controller\Processor\CheckProcessor $checkProcessor,
        \Magento\Framework\Controller\Result\RawFactory $rawResultFactory
    ) {
        $this->checkProcessor = $checkProcessor;
        $this->rawResultFactory = $rawResultFactory;

        parent::__construct($context);
    }

    public function execute()
    {
        if (! $this->getRequest()->isPost()) {
            return;
        }

        try {
            $params = $this->getRequest()->getParams();
            $data = $this->prepareResponse($params);

            $order = $data['order'];
            $response = $data['response'];

            $case = $this->checkProcessor->execute($order, $response);
            if ($case === 'payment_ko_on_order_ok') {
                throw new ResponseException($response->getOutputForGateway($case));
            }

            return $this->renderResponse($response->getOutputForGateway($case));
        } catch (\Lyranetwork\Payzen\Model\ResponseException $e) {
            return $this->renderResponse($e->getMessage());
        }
    }

    protected function prepareResponse($params)
    {
        return $this->checkProcessor->prepareResponse($params);
    }

    protected function renderResponse($text)
    {
        $rawResult = $this->rawResultFactory->create();
        $rawResult->setContents($text);

        return $rawResult;
    }
}
