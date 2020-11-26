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
        'jquery',
        'Lyranetwork_Payzen/js/view/payment/method-renderer/payzen-abstract'
    ],
    function ($, Component) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Lyranetwork_Payzen/payment/payzen-oney',
                payzenOneyOption: window.checkoutConfig.payment.payzen_oney.availableOptions ?
                    window.checkoutConfig.payment.payzen_oney.availableOptions[0]['key'] : null
            },

            initObservable: function () {
                this._super().observe('payzenOneyOption');
                return this;
            },

            getData: function () {
                var data = this._super();
                data['additional_data']['payzen_oney_option'] = this.payzenOneyOption();

                return data;
            },

            showLabel: function () {
                return true;
            },

            getAvailableOptions: function () {
                return window.checkoutConfig.payment.payzen_oney.availableOptions;
            },

            getErrorMessage: function () {
                return $.cookie('payzen_oney_error');
            },

            isPlaceOrderActionAllowed: function () {
                if ($.cookie('payzen_oney_error')) {
                    return false;
                } else {
                    return this._super();
                }
            }
        });
    }
);
