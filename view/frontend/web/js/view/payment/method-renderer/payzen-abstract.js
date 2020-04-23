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
        'Magento_Checkout/js/view/payment/default'
    ],
    function ($, Component) {
        'use strict';

        return Component.extend({
            defaults: {
                redirectAfterPlaceOrder: false
            },

            getData: function () {
                var data = this._super();

                return $.extend(true, data, {
                    'additional_data': {}
                });
            },

            showLabel: function () {
                return false;
            },

            getCheckoutRedirectUrl: function () {
                return window.checkoutConfig.payment[this.item.method].checkoutRedirectUrl;
            },

            getModuleLogoUrl: function () {
                return window.checkoutConfig.payment[this.item.method].moduleLogoUrl;
            },

            getAvailableCcTypes: function () {
                return window.checkoutConfig.payment[this.item.method].availableCcTypes;
            },

            getEntryMode: function () {
                return window.checkoutConfig.payment[this.item.method].entryMode;
            },

            afterPlaceOrder: function () {
                // Order placed with payment_pending status, redirect to gateway.
                $.mage.redirect(this.getCheckoutRedirectUrl());
            }
        });
    }
);
