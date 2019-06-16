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
        'Lyranetwork_Payzen/js/view/payment/method-renderer/payzen-abstract',
        'Magento_Checkout/js/model/full-screen-loader'
    ],
    function ($, Component, fullScreenLoader) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Lyranetwork_Payzen/payment/payzen-gift',
                payzenCcType:  window.checkoutConfig.payment.payzen_gift.availableCcTypes ?
                    window.checkoutConfig.payment.payzen_gift.availableCcTypes[0]['value'] : null
            },

            initObservable: function () {
                this._super().observe('payzenCcType');

                return this;
            },
            
            getData: function () {
                var data = this._super();
                data['additional_data']['payzen_gift_cc_type'] = this.payzenCcType();

                return data;
            },

            showLabel: function () {
                return true;
            }
        });
    }
);
