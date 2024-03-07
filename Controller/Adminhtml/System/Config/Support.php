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
use Magento\Framework\Mail\Template\TransportBuilder;

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
     * @var TransportBuilder
     */
    protected $transportBuilder;

    /**
     * @var \Magento\User\Model\UserFactory
     */
    protected $userFactory;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Lyranetwork\Payzen\Helper\Data $dataHelper
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param TransportBuilder $transportBuilder
     * @param \Magento\User\Model\UserFactory $userFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Lyranetwork\Payzen\Helper\Data $dataHelper,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        TransportBuilder $transportBuilder,
        \Magento\User\Model\UserFactory $userFactory
    ) {
        parent::__construct($context);

        $this->dataHelper = $dataHelper;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->transportBuilder = $transportBuilder;
        $this->userFactory = $userFactory;
    }

    public function execute()
    {
        // Clear all messages in session.
        $this->messageManager->getMessages(true);

        $params = $this->getRequest()->getPost();

        if (isset($params['submitter']) && $params['submitter'] === 'payzen_send_support') {
            if (isset($params['sender']) && isset($params['subject']) && isset($params['message'])) {
                $sender = $params['sender'];
                $recipient = $this->dataHelper->getCommonConfigData('support_email');
                $subject = $params['subject'];
                $content = $params['message'];
                $senderName = $this->getUserName($sender);

                try {
                    $this->sendSupportEmail($subject, $content, $sender, $senderName, $recipient);
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

    public function sendSupportEmail(string $subject, string $content, string $fromEmail, string $fromName, string $toEmail)
    {
        $transport = $this->transportBuilder
            ->setTemplateIdentifier('payzen_support_email_template')
            ->setTemplateOptions([
                    'area' => \Magento\Backend\App\Area\FrontNameResolver::AREA_CODE,
                    'store' => \Magento\Store\Model\Store::DEFAULT_STORE_ID,
                ])
            ->setTemplateVars(['subject' => $subject, 'content' => $content])
            ->setFrom(['email' => $fromEmail, 'name' => $fromName])
            ->addTo($toEmail)
            ->getTransport();

        $transport->sendMessage();
    }

    public function getUserName(string $email)
    {
        try {
            $user = $this->userFactory->create()->load($email, 'email');
            if ($user->getId()) {
                return $user->getFirstName() . ' ' . $user->getLastName();
            } else {
                $this->dataHelper->log('An error occurred when trying to retrieve sender: User does not exist.');
            }
        } catch (\Exception $e) {
            $this->dataHelper->log('An error occurred when trying to retrieve sender. ' . $e->getMessage());
        }

        return $email;
    }
}
