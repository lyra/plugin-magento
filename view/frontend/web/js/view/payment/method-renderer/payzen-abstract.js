/**
 * PayZen V2-Payment Module version 2.1.1 for Magento 2.x. Support contact : support@payzen.eu.
 *
 * NOTICE OF LICENSE
 *
 * This source file is licensed under the Open Software License version 3.0
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category  payment
 * @package   payzen
 * @author    Lyra Network (http://www.lyra-network.com/)
 * @copyright 2014-2016 Lyra Network and contributors
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/*browser:true*/
/*global define*/
define(
    [
        'jquery',
        'Magento_Checkout/js/view/payment/default'
    ],
    function($, Component) {
        'use strict';

        return Component.extend({
            defaults: {
                redirectAfterPlaceOrder: false
            },

            getModuleLogoUrl: function() {
                return window.checkoutConfig.payment[this.item.method].moduleLogoUrl;
            },

            afterPlaceOrder: function() {
                // order placed with payment_pending status, redirect to platform
                $.mage.redirect(window.checkoutConfig.payment[this.item.method].checkoutRedirectUrl);
            }
        });
    }
);