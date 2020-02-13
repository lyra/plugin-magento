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
        'Lyranetwork_Payzen/js/view/payment/method-renderer/payzen-abstract'
    ],
    function (Component) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Lyranetwork_Payzen/payment/payzen-multi',
                payzenMultiOption: window.checkoutConfig.payment.payzen_multi.availableOptions ?
                    window.checkoutConfig.payment.payzen_multi.availableOptions[0]['key'] : null,
                payzenCcType: window.checkoutConfig.payment.payzen_multi.availableCcTypes ?
                    window.checkoutConfig.payment.payzen_multi.availableCcTypes[0]['value'] : null
            },

            initObservable: function () {
                this._super();
                this.observe('payzenCcType');
                this.observe('payzenMultiOption');

                return this;
            },

            getData: function () {
                var data = this._super();

                if (this.getEntryMode() == 2) {
                    data['additional_data']['payzen_multi_cc_type'] = this.payzenCcType();
                }

                data['additional_data']['payzen_multi_option'] = this.payzenMultiOption();

                return data;
            },

            showLabel: function () {
                return true;
            },

            getAvailableOptions: function () {
                return window.checkoutConfig.payment.payzen_multi.availableOptions;
            }
        });
    }
);