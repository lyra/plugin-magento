<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento 2. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
namespace Lyranetwork\Payzen\Controller\Adminhtml\System\Config;

use Magento\Framework\DataObject;

class Support extends \Magento\Backend\App\AbstractAction
{
    /**
     * @var \Lyranetwork\Payzen\Helper\Data
     */
    protected $dataHelper;

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Lyranetwork\Payzen\Helper\Data $dataHelper
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Lyranetwork\Payzen\Helper\Data $dataHelper,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
    ) {
        parent::__construct($context);

        $this->dataHelper = $dataHelper;
        $this->resultJsonFactory = $resultJsonFactory;
    }

    public function execute()
    {
        // Clear all messages in session.
        $this->messageManager->getMessages(true);

        $params = $this->getRequest()->getPost();

        if (isset($params['submitter']) && $params['submitter'] === 'payzen_send_support') {
            if (isset($params['sender']) && isset($params['subject']) && isset($params['message'])) {
                $recipient = $this->dataHelper->getCommonConfigData('support_email');
                $subject = $params['subject'];
                $content = $params['message'];

                try {
                    $email = new \Zend_Mail('UTF-8');
                    $email->setSubject($subject);
                    $email->setBodyHtml($content);
                    $email->setFrom($params['sender']);
                    $email->addTo($recipient);

                    $email->send();
                    $this->messageManager->addSuccessMessage(__('Thank you for contacting us. Your email has been successfully sent.'));
                } catch (\Exception $e) {
                    $this->dataHelper->log('An error occurred when trying to send email to Support: ' . $e->getMessage());
                    $this->messageManager->addErrorMessage(__('An error has occurred. Your email was not sent.'));
                }
            } else {
                $this->messageManager->addWarningMessage(__('Please make sure to configure all required fields.'));
            }
        }

        $result = $this->resultJsonFactory->create();

        $data = new DataObject();
        $data->setData('success', true);

        return $result->setData($data->getData());
    }
}
