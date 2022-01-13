/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento 2. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

/*browser:true*/
/*global define*/
define(
    [
        'Lyranetwork_Payzen/js/view/payment/method-renderer/payzen-abstract',
        'Magento_Checkout/js/action/select-payment-method',
        'Magento_Checkout/js/checkout-data'
    ],
    function(
        Component,
        selectPaymentMethodAction,
        checkoutData
    ) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Lyranetwork_Payzen/payment/payzen-other',
                payzenOtherOption: window.checkoutConfig.payment.payzen_other.availableOptions ?
                        window.checkoutConfig.payment.payzen_other.availableOptions[0]['key'] : null,
            },

            initObservable: function() {
                this._super();
                this.observe('payzenOtherOption');

                return this;
            },

            getData: function() {
                var data = this._super();

                data['additional_data']['payzen_other_option'] = this.payzenOtherOption();

                return data;
            },

            /**
             * Get payment method code
             */
            getOptionCode: function(option) {
                return this.getCode() + '_' + option;
            },

            /**
             * Get payment method data
             */
            getOptionData: function(method) {
                var data = this.getData();
                data['method'] =  method;

                return data;
            },

            selectOptionPaymentMethod: function(option) {
                var method = this.getCode() + '_' + option;

                selectPaymentMethodAction(this.getOptionData(method));
                checkoutData.setSelectedPaymentMethod(method);

                return true;
            },

            showLabel: function() {
                return true;
            },

            getAvailableOptions: function() {
                return window.checkoutConfig.payment.payzen_other.availableOptions;
            },

            getRegroupMode: function() {
                return window.checkoutConfig.payment.payzen_other.regroupMode;
            }
        });
    }
);
