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

/**
 * Admin configuraion controller.
 */
class Reset extends \Magento\Backend\App\AbstractAction
{
    /**
     * @var \Magento\Config\Model\ResourceModel\Config
     */
    protected $resourceConfig;

    /**
     * @var \Magento\Framework\Cache\FrontendInterface
     */
    protected $cache;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Config\Model\ResourceModel\Config $resourceConfig
     * @param \Magento\Framework\Cache\FrontendInterface $cache
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Config\Model\ResourceModel\Config $resourceConfig,
        \Magento\Framework\Cache\FrontendInterface $cache
    ) {
        parent::__construct($context);

        $this->resourceConfig = $resourceConfig;
        $this->cache = $cache;
    }

    public function execute()
    {
        // Clear all messages in session.
        $this->messageManager->getMessages(true);

        // Retrieve write connection.
        $connection = $this->resourceConfig->getConnection();

        // Get config_data table name & execute delete query.
        $where = [];
        $where[] = $connection->quoteInto('path REGEXP ?', '^payment/payzen_[a-z0-9]+/[a-z0-9_]+$');
        $where[] = $connection->quoteInto('path REGEXP ?', '^payzen/general/[a-z0-9_]+$');

        $connection->delete($this->resourceConfig->getMainTable(), implode(' OR ', $where));

        // Clear cache.
        $this->cache->clean();

        $this->messageManager->addSuccess(
            __('The configuration of the PayZen module has been successfully reset.')
        );

        // Redirect to payment config editor.
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        return $resultRedirect->setPath(
            'adminhtml/system_config/edit',
            ['section' => 'payment', '_nosid' => true]
        );
    }
}
