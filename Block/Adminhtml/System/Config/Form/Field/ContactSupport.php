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

/**
 * Custom renderer for the contact support link.
 */
class ContactSupport extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * @var \Lyranetwork\Payzen\Helper\Data
     */
    protected $dataHelper;

    /**
     * @var \Magento\Framework\Locale\ResolverInterface
     */
    protected $localeResolver;

    /**
     * @var \Magento\Framework\Module\FullModuleList
     */
    protected $fullModuleList;

    /**
     * @var \Magento\Backend\Model\Auth\Session
     */
    protected $authSession;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Lyranetwork\Payzen\Helper\Data $dataHelper,
        \Magento\Framework\Locale\ResolverInterface $localeResolver,
        \Magento\Framework\Module\FullModuleList $fullModuleList,
        \Magento\Backend\Model\Auth\Session $authSession,
        array $data = []
    )
    {
        parent::__construct($context, $data);

        $this->dataHelper = $dataHelper;
        $this->localeResolver = $localeResolver;
        $this->fullModuleList = $fullModuleList;
        $this->authSession = $authSession;
    }

    public function getStoreInfo($order = null)
    {
        $info = [];

        $storeId = $order ? $order->getStore()->getId() : null;

        $info['shop-id'] = $this->dataHelper->getCommonConfigData('site_id', $storeId);
        $info['context-mode'] = $this->dataHelper->getCommonConfigData('ctx_mode', $storeId);
        $info['sign-algo'] = $this->dataHelper->getCommonConfigData('sign_algo', $storeId);

        $info['contrib'] = $this->dataHelper->getContribParam();
        $info['integration-mode'] = $this->dataHelper->getCardDataEntryMode($storeId);

        $modulesList = $this->fullModuleList->getNames();
        foreach ($modulesList as $id => $module) {
            // Do not include Magento default modules.
            if (strpos($module, 'Magento_') === 0) {
                unset($modulesList[$id]);
            }
        }

        $info['plugins'] = implode(' / ', $modulesList);
        $info['first-name'] = $this->authSession->getUser()->getFirstName();
        $info['last-name'] = $this->authSession->getUser()->getLastName();
        $info['from-email'] = $this->authSession->getUser()->getEmail();
        $info['to-email'] = $this->dataHelper->getCommonConfigData('support_email');
        $info['language'] = strtolower(substr($this->localeResolver->getLocale(), 0, 2));

        return $info;
    }

    /**
     * Set template to itself.
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();

        if (! $this->getTemplate()) {
            $this->setTemplate('Lyranetwork_Payzen::system/config/form/field/contact_support.phtml');
        }

        return $this;
    }

    /**
     * Unset some non-related element parameters.
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $element->unsScope()
            ->unsCanUseWebsiteValue()
            ->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * Get the button and scripts contents.
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $fieldConfig = $element->getFieldConfig();

        $this->addData(
            [
                'button_label' => __($fieldConfig['button_label']),
                'button_url' => $this->getUrl(
                    $fieldConfig['button_url'],
                    [
                        '_nosid' => true
                    ]
                 ),
                'html_id' => $element->getHtmlId()
            ]
       );

        return $this->_toHtml();
    }

    public function sendMailUrl()
    {
        return $this->getUrl('payzen/system_config/support', ['_nosid' => true]);
    }
}
