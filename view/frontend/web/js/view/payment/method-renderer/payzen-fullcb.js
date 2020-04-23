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
                template: 'Lyranetwork_Payzen/payment/payzen-fullcb',
                payzenFullcbOption: window.checkoutConfig.payment.payzen_fullcb.availableOptions ?
                    window.checkoutConfig.payment.payzen_fullcb.availableOptions[0]['key'] : null
            },

            initObservable: function () {
                this._super().observe('payzenFullcbOption');
                return this;
            },

            getData: function () {
                var data = this._super();
                data['additional_data']['payzen_fullcb_option'] = this.payzenFullcbOption();

                return data;
            },

            getAvailableOptions: function () {
                return window.checkoutConfig.payment.payzen_fullcb.availableOptions;
            }
        });
    }
);
