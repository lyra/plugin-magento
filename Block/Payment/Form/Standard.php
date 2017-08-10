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
namespace Lyranetwork\Payzen\Block\Payment\Form;

class Standard extends Payzen
{
    protected $_template = 'Lyranetwork_Payzen::payment/form/standard.phtml';

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Lyranetwork\Payzen\Helper\Data $dataHelper
     * @param \Magento\Customer\Model\Session $customerSession
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Lyranetwork\Payzen\Helper\Data $dataHelper,
        \Magento\Customer\Model\Session $customerSession,
        array $data = []
    ) {
        $this->customerSession = $customerSession;

        parent::__construct($context, $dataHelper, $data);
    }

    public function getAvailableCcTypes()
    {
        return $this->getMethod()->getAvailableCcTypes();
    }

    public function getCcTypeNetwork($code)
    {
        if ($code == 'AMEX') {
            return 'AMEX';
        } elseif (in_array($code, ['CB', 'VISA', 'VISA_ELECTRON', 'MASTERCARD', 'MAESTRO', 'E-CARTEBLEUE'])) {
            return 'CB';
        } else {
            return null;
        }
    }

    public function getCcTypeImageSrc($card)
    {
        $card = 'cc/' . strtolower($card) . '.png';

        if ($this->dataHelper->isUploadFileImageExists($card)) {
            return $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA)
                . 'payzen/images/' . $card;
        } else {
            return $this->getViewFileUrl('Lyranetwork_Payzen::images/' . $card);
        }
    }

    public function getLoggedCustomer()
    {
        return $this->customerSession->getCustomer();
    }

    public function isLocalCcInfo()
    {
        return $this->getMethod()->isLocalCcInfo();
    }

    public function isLocalCcType()
    {
        return $this->getMethod()->isLocalCcType();
    }
}
