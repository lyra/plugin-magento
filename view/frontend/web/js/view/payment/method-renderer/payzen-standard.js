/**
 * PayZen V2-Payment Module version 2.1.3 for Magento 2.x. Support contact : support@payzen.eu.
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
                template: 'Lyranetwork_Payzen/payment/payzen-standard',
                payzenCcType: window.checkoutConfig.payment.payzen_standard.availableCcTypes[0]['value'] || null
            },

            afterPlaceOrder: function () {
                if (this.getEntryMode() == 3) {
                    // iframe mode
                    fullScreenLoader.stopLoader();

                    $('.payment-method._active .payment-method-content .payzen-form').hide();
                    $('.payment-method._active .payment-method-content .payzen-iframe').show();

                    var iframe = $('.payment-method._active .payment-method-content .payzen-iframe.iframe');
                    if (iframe && iframe.length) {
                        var url = this.getCheckoutRedirectUrl() + '?iframe=true';
                        iframe.attr('src', url);
                    }
                } else {
                    this._super();
                }
            }
        });
    }
);
