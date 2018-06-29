<?php
/**
 * PayZen V2-Payment Module version 2.3.0 for Magento 2.x. Support contact : support@payzen.eu.
 *
 * NOTICE OF LICENSE
 *
 * This source file is licensed under the Open Software License version 3.0
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/osl-3.0.php
 *
 * @author    Lyra Network (http://www.lyra-network.com/)
 * @copyright 2014-2018 Lyra Network and contributors
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @category  payment
 * @package   payzen
 */
namespace Lyranetwork\Payzen\Model\Method;

class Choozeo extends Payzen
{

    protected $_code = \Lyranetwork\Payzen\Helper\Data::METHOD_CHOOZEO;

    protected $_formBlockType = \Lyranetwork\Payzen\Block\Payment\Form\Choozeo::class;

    protected $_canUseInternal = false;

    protected $currencies = ['EUR'];

    protected function setExtraFields($order)
    {
        // override some form data
        $this->payzenRequest->set('validation_mode', '0');
        $this->payzenRequest->set('cust_status', 'PRIVATE');

        // send phone number as cell phone
        $this->payzenRequest->set('cust_cell_phone', $order->getBillingAddress()->getTelephone());

        // override with selected Choozeo payment card
        $info = $this->getInfoInstance();
        $this->payzenRequest->set('payment_cards', $info->getCcType());
    }

    /**
     * Assign data to info model instance.
     *
     * @param \Magento\Framework\DataObject $data
     * @return $this
     */
    public function assignData(\Magento\Framework\DataObject $data)
    {
        // reset payment method specific data
        $this->resetData();

        parent::assignData($data);
        $info = $this->getInfoInstance();

        $payzenData = $this->extractPayzenData($data);

        // load option informations
        $option = $payzenData->getData('payzen_choozeo_option');
        $info->setCcType($option)->setAdditionalInformation(\Lyranetwork\Payzen\Helper\Payment::CHOOZEO_OPTION, $option);

        return $this;
    }

    /**
     * Return true if the method can be used at this time.
     *
     * @param \Magento\Quote\Api\Data\CartInterface|null $quote
     * @return bool
     */
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        if (! parent::isAvailable($quote)) {
            return false;
        }

        $amount = $quote ? $quote->getBaseGrandTotal() : null;
        if ($amount) {
            $options = $this->getAvailableOptions($amount);
            return count($options) > 0;
        }

        return true;
    }

    /**
     * Return available payment options to be displayed on payment method list page.
     *
     * @param double $amount
     *            a given amount
     * @return array[string][array] An array "$code => $option" of availables options
     */
    public function getAvailableOptions($amount = null)
    {
        $configOptions = $this->dataHelper->unserialize($this->getConfigData('choozeo_payment_options'));

        /** @var array[string][string] $options */
        $options = [
            'EPNF_3X' => 'Choozeo 3X CB',
            'EPNF_4X' => 'Choozeo 4X CB'
        ];

        $availOptions = [];
        if (is_array($configOptions) && ! empty($configOptions)) {
            foreach ($configOptions as $code => $value) {
                if (empty($value)) {
                    continue;
                }

                if ((! $amount || ! $value['amount_min'] || $amount > $value['amount_min'])
                    && (! $amount || ! $value['amount_max'] || $amount < $value['amount_max'])) {

                    $value['label'] = $options[$value['code']];

                    // option will be available
                    $availOptions[$code] = $value;
                }
            }
        }

        return $availOptions;
    }
}
