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
                template: 'Lyranetwork_Payzen/payment/payzen-sepa',
                payzenUseIdentifier: 1,
            },

            initObservable: function () {
                this._super();
                this.observe('payzenUseIdentifier');

                return this;
            },

            getData: function () {
                var data = this._super();

                if (this.isOneClick()) {
                    data['additional_data']['payzen_sepa_use_identifier'] = this.payzenUseIdentifier();
                }

                return data;
            },

            isOneClick: function () {
                return window.checkoutConfig.payment[this.item.method].oneClick || false;
            },

            getMaskedPan: function () {
                return window.checkoutConfig.payment[this.item.method].maskedPan || null;
            },

            updatePaymentBlock: function (blockName) {
                $('.payment-method._active .payment-method-content .payzen-identifier li.payzen-sepa-block').hide();
                $('li.payzen-sepa-' + blockName + '-block').show();
            },
        });
    }
);
