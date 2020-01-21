<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento 2. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
namespace Lyranetwork\Payzen\Model\Method;

use Lyranetwork\Payzen\Model\System\Config\Source\ChoozeoCountry;

class Choozeo extends Payzen
{

    protected $_code = \Lyranetwork\Payzen\Helper\Data::METHOD_CHOOZEO;
    protected $_formBlockType = \Lyranetwork\Payzen\Block\Payment\Form\Choozeo::class;

    protected $_canUseInternal = false;

    protected $currencies = ['EUR'];

    protected function setExtraFields($order)
    {
        // Override some form data.
        $this->payzenRequest->set('validation_mode', '0');
        $this->payzenRequest->set('cust_status', 'PRIVATE');
        $this->payzenRequest->set('cust_country', 'FR');

        // Override with selected Choozeo payment card.
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
        parent::assignData($data);

        $info = $this->getInfoInstance();

        $payzenData = $this->extractPaymentData($data);

        // Load option informations.
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
     * To check billing country is allowed for Choozeo payment method.
     *
     * @return bool
     */
    public function canUseForCountry($country)
    {
        if ($this->getConfigData('allowspecific') == 1) {
            $availableCountries = explode(',', $this->getConfigData('specificcountry'));
            return in_array($country, $availableCountries);
        } else {
            return in_array($country, ChoozeoCountry::$availableCountries);
        }
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

                    // Option will be available.
                    $availOptions[$code] = $value;
                }
            }
        }

        return $availOptions;
    }
}
