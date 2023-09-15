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
        'Magento_Checkout/js/view/payment/default',
        'mage/url'
    ],
    function($, Component, url) {
        'use strict';

        // Reload payment page to update Oney error message cookie.
        $(window).on('hashchange', function(e) {
            if ((window.location.hash === '#payment') && $('#payzen_oney').length) {
                $('#payzen_oney_message').hide();
                $('#payment_form_payzen_oney').hide();
                window.location.reload();
            }
        });

        $(document).ready(function() {
            setTimeout(function() {
                var message = '';
                if ($('#payzen_oney').length) {
                    // Show Oney error message if any.
                    message = $.cookie('payzen_oney_error'); // Get error message from cookie.

                    if (message) {
                        html = '<div id="payzen_oney_message" style="margin-bottom: 25px; width: 100%; background: #fae5e5; color: #e02b27; padding: 12px 0px 12px 25px; font-size: 1.3rem;">'
                            + '<span>' + message + '</span></div>';

                        $('#payzen_oney_message').html(html);
                        $('#payment_form_payzen_oney').hide();
                    }
                }

                var reloading = sessionStorage.getItem('reloading');
                if (reloading) {
                    sessionStorage.removeItem('reloading');

                    // Show success/fail message after deleting payment means.
                    message = sessionStorage.getItem('message');
                    var success = sessionStorage.getItem('success');
                    var identifier = sessionStorage.getItem('identifier');

                    var html = '<div style="width: 100%; background: #fae5e5; color: #e02b27; padding: 12px 0px 12px 25px; font-size: 1.3rem;">' + message + '</div>';
                    if (success === 'true') {
                        html = '<div style="width: 100%; background: #e5efe5; color: #006400; padding: 12px 0px 12px 25px; font-size: 1.3rem;">' + message + '</div>';
                    }

                    $('#' + identifier).html(html);
                    $('#' + identifier).delay(2000).hide(0);
                }
            }, 1000);
        });

        return Component.extend({
            defaults: {
                redirectAfterPlaceOrder: false
            },

            getData: function() {
                var data = this._super();

                return $.extend(true, data, {
                    'additional_data': {}
                });
            },

            showLabel: function() {
                return false;
            },

            getCheckoutRedirectUrl: function() {
                return window.checkoutConfig.payment[this.item.method].checkoutRedirectUrl;
            },

            getModuleLogoUrl: function() {
                return window.checkoutConfig.payment[this.item.method].moduleLogoUrl;
            },

            getAvailableCcTypes: function() {
                return window.checkoutConfig.payment[this.item.method].availableCcTypes;
            },

            getEntryMode: function() {
                return window.checkoutConfig.payment[this.item.method].entryMode;
            },

            getRestPopinMode: function() {
                return window.checkoutConfig.payment[this.item.method].popinMode;
            },

            getCompactMode: function() {
                return window.checkoutConfig.payment[this.item.method].compactMode;
            },

            getGroupThreshold: function() {
                return window.checkoutConfig.payment[this.item.method].groupThreshold;
            },

            getDisplayTitle: function() {
                return window.checkoutConfig.payment[this.item.method].displayTitle;
            },

            afterPlaceOrder: function() {
                // Order placed with payment_pending status, redirect to gateway.
                $.mage.redirect(this.getCheckoutRedirectUrl());
            },

            getPaymentMeansUrl: function() {
                return url.build('payzen/customer/index');
            }
        });
    }
);
