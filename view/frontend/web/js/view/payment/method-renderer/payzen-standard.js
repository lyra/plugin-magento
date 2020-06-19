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
        'mage/url',
        'mage/storage',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/action/set-payment-information',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Checkout/js/model/error-processor',
        'Magento_Checkout/js/model/full-screen-loader'
    ],
    function ($, Component, url, storage, quote, setPaymentInformationAction, additionalValidators, errorProcessor, fullScreenLoader) {
        'use strict';

        // Use default messages for these errors.
        const DFAULT_MESSAGES = [
            'CLIENT_300', 'CLIENT_304', 'CLIENT_502', 'PSP_539'
        ];

        const RECOVERABLE_ERRORS = [
            'CLIENT_300', 'CLIENT_304', 'CLIENT_502',
            'PSP_539', 'CLIENT_001', 'CLIENT_101',
            'CLIENT_301', 'CLIENT_302', 'CLIENT_303',
            'PSP_003', 'PSP_108', 'ACQ_001', 'PSP_099'
        ];

        var ERROR_MESSAGES = {
            fr: {
                CLIENT_001: 'Le paiement est refusé. Essayez de payer avec une autre carte.',
                CLIENT_101: 'Le paiement est annulé.',
                CLIENT_301: 'Le numéro de carte est invalide. Vérifiez le numéro et essayez à nouveau.',
                CLIENT_302: 'La date d\'expiration est invalide. Vérifiez la date et essayez à nouveau.',
                CLIENT_303: 'Le code de sécurité CVV est invalide. Vérifiez le code et essayez à nouveau.',
                CLIENT_999: 'Une erreur technique est survenue. Merci de réessayer plus tard.',

                INT_999: 'Une erreur technique est survenue. Merci de réessayer plus tard.',

                PSP_003: 'Le paiement est refusé. Essayez de payer avec une autre carte.',
                PSP_099: 'Trop de tentatives ont été effectuées. Merci de réessayer plus tard.',
                PSP_108: 'Le formulaire a expiré. Veuillez rafraîchir la page.',
                PSP_999: 'Une erreur est survenue durant le processus de paiement.',

                ACQ_001: 'Le paiement est refusé. Essayez de payer avec une autre carte.',
                ACQ_999: 'Une erreur est survenue durant le processus de paiement.'
            },

            en: {
                CLIENT_001: 'Payment is refused. Try to pay with another card.',
                CLIENT_101: 'Payment is cancelled.',
                CLIENT_301: 'The card number is invalid. Please check the number and try again.',
                CLIENT_302: 'The expiration date is invalid. Please check the date and try again.',
                CLIENT_303: 'The card security code (CVV) is invalid. Please check the code and try again.',
                CLIENT_999: 'A technical error has occurred. Please try again later.',

                INT_999: 'A technical error has occurred. Please try again later.',

                PSP_003: 'Payment is refused. Try to pay with another card.',
                PSP_099: 'Too many attempts. Please try again later.',
                PSP_108: 'The form has expired. Please refresh the page.',
                PSP_999: 'An error has occurred during the payment process.',

                ACQ_001: 'Payment is refused. Try to pay with another card.',
                ACQ_999: 'An error has occurred during the payment process.'
            },

            de: {
                CLIENT_001: 'Die Zahlung wird abgelehnt. Versuchen Sie, mit einer anderen Karte zu bezahlen.',
                CLIENT_101: 'Die Zahlung wird storniert.',
                CLIENT_301: 'Die Kartennummer ist ungültig. Bitte überprüfen Sie die Nummer und versuchen Sie es erneut.',
                CLIENT_302: 'Das Verfallsdatum ist ungültig. Bitte überprüfen Sie das Datum und versuchen Sie es erneut.',
                CLIENT_303: 'Der Kartenprüfnummer (CVC) ist ungültig. Bitte überprüfen Sie den Nummer und versuchen Sie es erneut.',
                CLIENT_999: 'Ein technischer Fehler ist aufgetreten. Bitte Versuchen Sie es später erneut.',

                INT_999: 'Ein technischer Fehler ist aufgetreten. Bitte Versuchen Sie es später erneut.',

                PSP_003: 'Die Zahlung wird abgelehnt. Versuchen Sie, mit einer anderen Karte zu bezahlen.',
                PSP_099: 'Zu viele Versuche. Bitte Versuchen Sie es später erneut.',
                PSP_108: 'Das Formular ist abgelaufen. Bitte aktualisieren Sie die Seite.',
                PSP_999: 'Ein Fehler ist während dem Zahlungsvorgang unterlaufen.',

                ACQ_001: 'Die Zahlung wird abgelehnt. Versuchen Sie, mit einer anderen Karte zu bezahlen.',
                ACQ_999: 'Ein Fehler ist während dem Zahlungsvorgang unterlaufen.'
            },

            es: {
                CLIENT_001: 'El pago es rechazado. Intenta pagar con otra tarjeta.',
                CLIENT_101: 'Se cancela el pago.',
                CLIENT_301: 'El número de tarjeta no es válido. Por favor, compruebe el número y vuelva a intentarlo.',
                CLIENT_302: 'La fecha de caducidad no es válida. Por favor, compruebe la fecha y vuelva a intentarlo.',
                CLIENT_303: 'El código de seguridad de la tarjeta (CVV) no es válido. Por favor revise el código y vuelva a intentarlo.',
                CLIENT_999: 'Ha ocurrido un error técnico. Por favor, inténtelo de nuevo más tarde.',

                INT_999: 'Ha ocurrido un error técnico. Por favor, inténtelo de nuevo más tarde.',

                PSP_003: 'El pago es rechazado. Intenta pagar con otra tarjeta.',
                PSP_099: 'Demasiados intentos. Por favor, inténtelo de nuevo más tarde.',
                PSP_108: 'El formulario ha expirado. Por favor, actualice la página.',
                PSP_999: 'Ocurrió un error en el proceso de pago.',

                ACQ_001: 'El pago es rechazado. Intenta pagar con otra tarjeta.',
                ACQ_999: 'Ocurrió un error en el proceso de pago.'
            }
        };

        return Component.extend({
            defaults: {
                template: 'Lyranetwork_Payzen/payment/payzen-standard',
                payzenCcType:  window.checkoutConfig.payment.payzen_standard.availableCcTypes ?
                    window.checkoutConfig.payment.payzen_standard.availableCcTypes[0]['value'] : null,
                payzenUseIdentifier: 1,
                payload: null,
                iframeDisplayed: false
            },

            initObservable: function () {
                this._super();
                this.observe('payzenCcType');
                this.observe('payzenUseIdentifier');

                return this;
            },

            getData: function () {
                var data = this._super();

                if (this.isOneClick()) {
                    data['additional_data']['payzen_standard_use_identifier'] = this.payzenUseIdentifier();
                }

                if (this.getEntryMode() == 2) {
                    data['additional_data']['payzen_standard_cc_type'] = this.payzenCcType();
                }

                return data;
            },

            getIframeLoaderUrl: function () {
                return window.checkoutConfig.payment[this.item.method].iframeLoaderUrl || null;
            },

            isOneClick: function () {
                return window.checkoutConfig.payment[this.item.method].oneClick || false;
            },

            getMaskedPan: function () {
                return window.checkoutConfig.payment[this.item.method].maskedPan || null;
            },

            getRestFormToken: function () {
                return window.checkoutConfig.payment.payzen_standard.restFormToken || null;
            },

            getLanguage: function () {
                return window.checkoutConfig.payment.payzen_standard.language || null;
            },

            updatePaymentBlock: function (blockName) {
                $('.payment-method._active .payment-method-content .payzen-identifier li.payzen-block').hide();
                $('li.payzen-standard-' + blockName + '-block').show();
            },

            initRestEvents: function (elts) { // To be called after kr-embedded div is added to DOM.
                if (!elts || !elts.length) {
                    return;
                }

                var me = this;

                require(['krypton'], function (KR) {
                    KR.setFormConfig({
                        formToken: me.getRestFormToken()
                    }).then(
                        function(v) {
                            var KR = v.KR;
                            KR.onFocus(function(e) {
                                $('#payzen_rest_form .kr-form-error').html('');
                            });

                            KR.onError(function (e) {
                                fullScreenLoader.stopLoader();
                                me.isPlaceOrderActionAllowed(true);

                                // Not recoverable error, reload page after a while.
                                if (RECOVERABLE_ERRORS.indexOf(e.errorCode) === -1) {
                                    setTimeout(function() {
                                        window.location.reload();
                                    }, 4000);
                                }

                                var msg = '';
                                if (DFAULT_MESSAGES.indexOf(e.errorCode) > -1) {
                                    msg = e.errorMessage;
                                    var endsWithDot = (msg.lastIndexOf('.') == (msg.length - 1) && msg.lastIndexOf('.') >= 0);

                                    msg += (endsWithDot ? '' : '.');
                                } else {
                                    msg = me.translateError(e.errorCode);
                                }

                                $('#payzen_rest_form .kr-form-error').html('<span style="color: red;"><span>' + msg + '</span></span>');
                            });
                        }
                    );
                });
            },

            placeOrderClick: function () {
                var me = this;

                if ((me.getEntryMode() == 4) && me.getRestFormToken()) {
                    $('#payzen_rest_form .kr-form-error').html('');

                    if (!additionalValidators.validate()) {
                        return;
                    }

                    fullScreenLoader.startLoader();
                    me.isPlaceOrderActionAllowed(false);

                    var newPayload = JSON.stringify({
                        cartId: quote.getQuoteId(),
                        email: quote.guestEmail,
                        paymentMethod: me.getData(),
                        billingAddress: quote.billingAddress()
                    });

                    if (me.payload && (me.payload === newPayload)) {
                        KR.submit();
                    } else {
                        me.payload = newPayload;

                        $.when(
                            setPaymentInformationAction(me.messageContainer, me.getData())
                        ).done(function () {
                            me.refreshToken();
                        }).fail(function (response) {
                            errorProcessor.process(response, me.messageContainer);
                            fullScreenLoader.stopLoader();
                            me.isPlaceOrderActionAllowed(true);
                        });
                    }
                } else {
                    me.placeOrder();
                }
            },

            refreshToken: function () {
                var me = this;

                storage.post(
                    url.build('payzen/payment_rest/token?form_key=' + $.mage.cookies.get('form_key'))
                ).done(function (response) {
                    if (response.token) {
                        KR.setFormConfig({
                            formToken: response.token
                        }).then(
                            function(v) {
                                var KR = v.KR;
                                KR.submit();
                            }
                        );
                    } else {
                        // Should not happen, this case is managed by failure callback.
                        console.log('Empty form token returned by refresh.');
                    }
                }).fail(function (response) {
                    errorProcessor.process(response, me.messageContainer);
                    fullScreenLoader.stopLoader();
                    me.isPlaceOrderActionAllowed(true);
                });
            },

            translateError: function (code) {
                var lang = this.getLanguage();
                var messages = ERROR_MESSAGES.hasOwnProperty(lang) ? ERROR_MESSAGES[lang] : ERROR_MESSAGES['en'];

                if (!messages.hasOwnProperty(code)) {
                    var index = code.lastIndexOf('_');
                    code = code.substring(0, index + 1) + '999';
                }

                return messages[code];
            },

            initIframeEvents: function (elts) { // To be called after iframe loading.
                if (!elts || !elts.length) {
                    return;
                }

                var me = this;

                quote.paymentMethod.subscribe(function(method) {
                    if ((method.method === 'payzen_standard') || !me.iframeDisplayed) {
                        return;
                    }

                    var iframe = $('.payment-method .payment-method-content iframe.payzen-iframe');
                    if (iframe && iframe.length) {
                        var cancelUrl = me.getIframeLoaderUrl() + '?mode=cancel' + '&' + Date.now();
                        iframe.attr('src', cancelUrl);

                        $('.payment-method .payment-method-content .payzen-iframe').hide();
                        $('.payment-method .payment-method-content .payzen-form').show();

                        me.isPlaceOrderActionAllowed(true);
                        me.iframeDisplayed = false;
                    }
                }, null, 'change');
            },

            afterPlaceOrder: function () {
                var me = this;

                if (me.getEntryMode() == 3) {
                    // Iframe mode.
                    fullScreenLoader.stopLoader();

                    $('.payment-method._active .payment-method-content .payzen-form').hide();
                    $('.payment-method._active .payment-method-content .payzen-iframe').show();

                    var iframe = $('.payment-method._active .payment-method-content iframe.payzen-iframe');
                    if (iframe && iframe.length) {
                        var redirectUrl = this.getCheckoutRedirectUrl() + '?iframe=true';
                        iframe.attr('src', redirectUrl);

                        me.iframeDisplayed = true;
                    }
                } else {
                    me._super();
                }
            }
        });
    }
);
