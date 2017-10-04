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
namespace Lyranetwork\Payzen\Model\Method;

class Cof3xcb extends Payzen
{

    protected $_code = \Lyranetwork\Payzen\Helper\Data::METHOD_COF3XCB;

    protected $_formBlockType = \Lyranetwork\Payzen\Block\Payment\Form\Cof3xcb::class;

    protected function setExtraFields($order)
    {
        $title = $order->getBillingAddress()->getPrefix();
        if (strlen($title) > 5) {
            $title = substr($title, 0, 5); // take the 5 first chars
        }
        $this->payzenRequest->set('cust_title', $title);

        $testMode = $this->payzenRequest->get('ctx_mode') == 'TEST';

        // override payment_cards
        $this->payzenRequest->set('payment_cards', $testMode ? 'COF3XCB_SB' : 'COF3XCB');
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

        return parent::assignData($data);
    }

    /**
     * Validate payment method information object.
     *
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function validate()
    {
        /*
         * calling parent validate function
         */
        parent::validate();

        $info = $this->getInfoInstance();
        if ($info instanceof \Mage\Sales\Model\Order\Payment) {
            $address = $info->getOrder()->getBillingAddress();
            $email = $info->getOrder()->getCustomerEmail();
        } else {
            $address = $info->getQuote()->getBillingAddress();
            $email = $info->getQuote()->getCustomerEmail();
        }

        $errors = [];

        $this->checkField($email, 'Email', $errors);
        $this->checkField($address->getFirstname(), 'First Name', $errors);
        $this->checkField($address->getLastname(), 'Last Name', $errors);
        $this->checkField($address->getPrefix(), 'Civility', $errors);
        $this->checkField($address->getStreet(1), 'Address', $errors);
        $this->checkField($address->getPostcode(), 'Postcode', $errors);
        $this->checkField($address->getCity(), 'City', $errors);
        $this->checkField($address->getCountryId(), 'Country', $errors);
        $this->checkField($address->getTelephone(), 'Telephone', $errors);

        if (count($errors) == 1) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('The field %1 is required for this payment mean.', implode(', ', $errors))
            );
        } elseif (count($errors) > 1) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('The fields %1 are required for this payment mean.', implode(', ', $errors))
            );
        }

        return $this;
    }

    private function checkField($field, $fieldName, array &$errors)
    {
        if (! $field) {
            $errors[] = __($fieldName);
        }

        return $this;
    }
}
