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
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/action/get-totals',
        'Magento_Customer/js/customer-data'
    ],
    function(
        $,
        Component,
        url,
        storage,
        quote,
        setPaymentInformationAction,
        additionalValidators,
        errorProcessor,
        fullScreenLoader,
        getTotalsAction,
        customerData
    ) {
        'use strict';

        // Use default messages for these errors.
        const DFAULT_MESSAGES = [
            'CLIENT_300', 'CLIENT_304', 'CLIENT_502', 'PSP_539'
        ];

        // Errors requiring page reloading.
        const EXPIRY_ERRORS = [
            'PSP_108', 'PSP_136', 'PSP_649'
        ];

        var ERROR_MESSAGES = {
            fr: {
                RELOAD_LINK: 'Veuillez rafraîchir la page.',
                CLIENT_001: 'Le paiement est refusé. Essayez de payer avec une autre carte.',
                CLIENT_101: 'Le paiement est annulé.',
                CLIENT_301: 'Le numéro de carte est invalide. Vérifiez le numéro et essayez à nouveau.',
                CLIENT_302: 'La date d\'expiration est invalide. Vérifiez la date et essayez à nouveau.',
                CLIENT_303: 'Le code de sécurité CVV est invalide. Vérifiez le code et essayez à nouveau.',
                CLIENT_999: 'Une erreur technique est survenue. Merci de réessayer plus tard.',

                INT_999: 'Une erreur technique est survenue. Merci de réessayer plus tard.',

                PSP_003: 'Le paiement est refusé. Essayez de payer avec une autre carte.',
                PSP_099: 'Trop de tentatives ont été effectuées. Merci de réessayer plus tard.',
                PSP_108: 'Le formulaire a expiré.',
                PSP_999: 'Une erreur est survenue durant le processus de paiement.',

                ACQ_001: 'Le paiement est refusé. Essayez de payer avec une autre carte.',
                ACQ_999: 'Une erreur est survenue durant le processus de paiement.'
            },

            en: {
                RELOAD_LINK: 'Please refresh the page.',
                CLIENT_001: 'Payment is refused. Try to pay with another card.',
                CLIENT_101: 'Payment is cancelled.',
                CLIENT_301: 'The card number is invalid. Please check the number and try again.',
                CLIENT_302: 'The expiration date is invalid. Please check the date and try again.',
                CLIENT_303: 'The card security code (CVV) is invalid. Please check the code and try again.',
                CLIENT_999: 'A technical error has occurred. Please try again later.',

                INT_999: 'A technical error has occurred. Please try again later.',

                PSP_003: 'Payment is refused. Try to pay with another card.',
                PSP_099: 'Too many attempts. Please try again later.',
                PSP_108: 'The form has expired.',
                PSP_999: 'An error has occurred during the payment process.',

                ACQ_001: 'Payment is refused. Try to pay with another card.',
                ACQ_999: 'An error has occurred during the payment process.'
            },

            de: {
                RELOAD_LINK: 'Bitte aktualisieren Sie die Seite.',
                CLIENT_001: 'Die Zahlung wird abgelehnt. Versuchen Sie, mit einer anderen Karte zu bezahlen.',
                CLIENT_101: 'Die Zahlung wird storniert.',
                CLIENT_301: 'Die Kartennummer ist ungültig. Bitte überprüfen Sie die Nummer und versuchen Sie es erneut.',
                CLIENT_302: 'Das Verfallsdatum ist ungültig. Bitte überprüfen Sie das Datum und versuchen Sie es erneut.',
                CLIENT_303: 'Der Kartenprüfnummer (CVC) ist ungültig. Bitte überprüfen Sie den Nummer und versuchen Sie es erneut.',
                CLIENT_999: 'Ein technischer Fehler ist aufgetreten. Bitte Versuchen Sie es später erneut.',

                INT_999: 'Ein technischer Fehler ist aufgetreten. Bitte Versuchen Sie es später erneut.',

                PSP_003: 'Die Zahlung wird abgelehnt. Versuchen Sie, mit einer anderen Karte zu bezahlen.',
                PSP_099: 'Zu viele Versuche. Bitte Versuchen Sie es später erneut.',
                PSP_108: 'Das Formular ist abgelaufen.',
                PSP_999: 'Ein Fehler ist während dem Zahlungsvorgang unterlaufen.',

                ACQ_001: 'Die Zahlung wird abgelehnt. Versuchen Sie, mit einer anderen Karte zu bezahlen.',
                ACQ_999: 'Ein Fehler ist während dem Zahlungsvorgang unterlaufen.'
            },

            es: {
                RELOAD_LINK: 'Por favor, actualice la página.',
                CLIENT_001: 'El pago es rechazado. Intenta pagar con otra tarjeta.',
                CLIENT_101: 'Se cancela el pago.',
                CLIENT_301: 'El número de tarjeta no es válido. Por favor, compruebe el número y vuelva a intentarlo.',
                CLIENT_302: 'La fecha de caducidad no es válida. Por favor, compruebe la fecha y vuelva a intentarlo.',
                CLIENT_303: 'El código de seguridad de la tarjeta (CVV) no es válido. Por favor revise el código y vuelva a intentarlo.',
                CLIENT_999: 'Ha ocurrido un error técnico. Por favor, inténtelo de nuevo más tarde.',

                INT_999: 'Ha ocurrido un error técnico. Por favor, inténtelo de nuevo más tarde.',

                PSP_003: 'El pago es rechazado. Intenta pagar con otra tarjeta.',
                PSP_099: 'Demasiados intentos. Por favor, inténtelo de nuevo más tarde.',
                PSP_108: 'El formulario ha expirado.',
                PSP_999: 'Ocurrió un error en el proceso de pago.',

                ACQ_001: 'El pago es rechazado. Intenta pagar con otra tarjeta.',
                ACQ_999: 'Ocurrió un error en el proceso de pago.'
            },

            pt: {
                RELOAD_LINK: 'Por favor, atualize a página.',
                CLIENT_001: 'O pagamento é rejeitado. Tente pagar com outro cartão.',
                CLIENT_101: 'O pagamento é cancelado.',
                CLIENT_301: 'O número do cartão é inválido. Por favor, cheque o número e tente novamente.',
                CLIENT_302: 'A data de expiração é inválida. Verifique a data e tente novamente.',
                CLIENT_303: 'O código de segurança do cartão (CVV) é inválido. Verifique o código e tente novamente.',
                CLIENT_999: 'Ocorreu um erro técnico. Por favor, tente novamente mais tarde.',

                INT_999: 'Ocorreu um erro técnico. Por favor, tente novamente mais tarde.',

                PSP_003: 'O pagamento é rejeitado. Tente pagar com outro cartão.',
                PSP_099: 'Muitas tentativas. Por favor, tente novamente mais tarde.',
                PSP_108: 'O formulário expirou.',
                PSP_999: 'Ocorreu um erro no processo de pagamento.',

                ACQ_001: 'O pagamento é rejeitado. Tente pagar com outro cartão.',
                ACQ_999: 'Ocorreu um erro no processo de pagamento.'
            }
        };

        var PLACE_ORDER = true;  // An order can be placed (created).
        var SHOW_ERROR = false;  // Show error message.
        var CAN_REFRESH_TOKEN = true; // Token can be refreshed.

        return Component.extend({
            defaults: {
                template: 'Lyranetwork_Payzen/payment/payzen-standard',
                payzenCcType:  window.checkoutConfig.payment.payzen_standard.availableCcTypes ?
                    window.checkoutConfig.payment.payzen_standard.availableCcTypes[0]['value'] : null,
                payzenUseIdentifier: 1,
                payload: null
            },

            initObservable: function() {
                this._super();
                this.observe('payzenCcType');
                this.observe('payzenUseIdentifier');

                return this;
            },

            getData: function() {
                var data = this._super();

                if (this.isOneClick()) {
                    data['additional_data']['payzen_standard_use_identifier'] = this.payzenUseIdentifier();
                }

                if (this.getEntryMode() == 2) { // Payment means selection on merchant site.
                    data['additional_data']['payzen_standard_cc_type'] = this.payzenCcType();
                }

                return data;
            },

            isOneClick: function() {
                return window.checkoutConfig.payment[this.item.method].oneClick || false;
            },

            getMaskedPan: function() {
                return window.checkoutConfig.payment[this.item.method].maskedPan || null;
            },

            getErrorMessage: function() {
                return window.checkoutConfig.payment.payzen_standard.errorMessage || null;
            },

            getRestFormToken: function() {
                return window.checkoutConfig.payment.payzen_standard.restFormToken || null;
            },

            getRestReturnUrl: function() {
                return window.checkoutConfig.payment[this.item.method].restReturnUrl || null;
            },

            getLanguage: function() {
                return window.checkoutConfig.payment.payzen_standard.language || null;
            },

            updatePaymentBlock: function(blockName) {
                $('.payment-method._active .payment-method-content .payzen-identifier li.payzen-block').hide();
                $('li.payzen-standard-' + blockName + '-block').show();
            },

            updateOrder: function() {
                return (window.checkoutConfig.payment.payzen_standard.updateOrder
                    && (window.checkoutConfig.payment.payzen_standard.updateOrder == '1'))
                    || false;
            },

            restoreCart: function() {
                if (SHOW_ERROR || !PLACE_ORDER) {
                    storage.post(
                        url.build('payzen/payment_rest/token?payzen_action=restore_cart&form_key=' + $.mage.cookies.get('form_key'))
                    ).done(function(response) {
                        console.log('Cart restored.');
                    }).fail(function(response) {
                        console.log('Cart could not be restored.');
                    });
                }
            },

            manageError: function (e) {
                var me = this;

                me.restoreCart();

                fullScreenLoader.stopLoader();
                me.isPlaceOrderActionAllowed(true);

                if (e !== null) {
                    var msg = '';
                    if (DFAULT_MESSAGES.indexOf(e.errorCode) > -1) {
                        msg = e.errorMessage;
                        var endsWithDot = (msg.lastIndexOf('.') == (msg.length - 1) && msg.lastIndexOf('.') >= 0);
                        msg += (endsWithDot ? '' : '.');
                    } else {
                        msg = me.translateError(e.errorCode);
                    }

                    // Expiration errors, display a link to refresh the page.
                    if (EXPIRY_ERRORS.indexOf(e.errorCode) >= 0) {
                        msg += ' <a href="#" onclick="window.location.reload(); return false;">'
                            + me.translateError('RELOAD_LINK') + '</a>';
                    }

                    $('#payzen_rest_form .kr-form-error').html('<span style="color: red;"><span>' + msg + '</span></span>');
                }

                return true;
            },

            showErrorMessage: function () {
                var me = this;

                SHOW_ERROR = true;
                me.restoreCart();

                fullScreenLoader.stopLoader();
                me.isPlaceOrderActionAllowed(true);
  
                var html = '<div id="payzen_oney_message" style="margin-bottom: 25px; width: 100%; background: #fae5e5; color: #e02b27; padding: 12px 0px 12px 25px; font-size: 1.3rem;">'
                    + '<span>' + ERROR_MESSAGES[me.getLanguage()]["PSP_999"] + '</span></div>';

                $('#payzen_standard_message').html(html);
            },

            checkPayload: function() {
                if (! CAN_REFRESH_TOKEN) {
                    return;
                }

                var me = this;

                var newPayload = me.getPayload();
                if (me.payload && (me.payload === newPayload)) {
                    // Check if form token amount is up to date.
                    var totals = quote.getTotals()();
                    if (totals) {
                        me.checkTokenAmount(totals['grand_total'], totals['quote_currency_code'], totals['base_grand_total'], totals['base_currency_code'], KR_RAW_DNA.amount);
                    }

                    return;
                }

                if (!quote.paymentMethod() || !quote.paymentMethod().hasOwnProperty('method') || quote.paymentMethod().method !== 'payzen_standard') {
                    // Not our payment method, do not refresh.
                    return;
                }

                me.payload = newPayload;
                me.refreshToken();
            },

            initRestEvents: function(elts) { // To be called after kr-embedded div is added to DOM.
                if (!elts || !elts.length) {
                    return;
                }

                var me = this;

                // Workaround to fix Magento bug: update order summary when modifying minicart.
                $(document).on('ajax:updateCartItemQty ajax:removeFromCart', function() {
                    me.payload = null;
                    getTotalsAction([]);
                });

                // Update embedded payment token if billing address has changed.
                quote.billingAddress.subscribe(function (address) {
                    if (address == null) {
                       // Address has not been saved yet.'
                       return;
                    }

                    me.checkPayload();
                });

                // Update embedded payment token if quote amount has changed.
                quote.totals.subscribe(function (totals) {
                    if (totals == null) {
                       // Order has already been placed.
                       return;
                    }

                    me.checkPayload();
                });

                require(['krypton'], function(KR) {
                    if (me.getCompactMode() === "1") {
                        KR.setFormConfig({ cardForm: { layout: "compact" }, smartForm: { layout: "compact" }});
                    }

                    if (!me.getGroupThreshold() && !isNaN(me.getGroupThreshold())) {
                        KR.setFormConfig({ smartForm: { groupingThreshold: me.getGroupThreshold() }});
                    }

                    KR.setFormConfig({
                        formToken: me.getRestFormToken(),
                        language: me.getLanguage(),
                        form: { smartform: { singlePaymentButton: { visibility: false }}}
                    }).then(
                        function(v) {
                            KR = v.KR;
                            KR.onFocus(function(e) {
                                $('#payzen_rest_form .kr-form-error').html('');
                            });

                            KR.onError(function(e) {
                                return me.manageError(e);
                            });

                            KR.onPopinClosed(function(e) {
                                me.restoreCart();
                            });

                            KR.onFormReady(() => {
                                me.hideSmartformPopinButton();
                            });
                        }
                    )
                });
            },

            placeOrderClick: function() {
                var me = this;

                if (PLACE_ORDER) {
                    me.placeOrder();
                } else {
                    if (SHOW_ERROR) {
                        SHOW_ERROR = false;
                        return;
                    }

                    if (! me.validate() || ! additionalValidators.validate() || me.isPlaceOrderActionAllowed() === false) {
                        return;
                    }

                    // It's a payment retry, an order has already been placed.
                    if ($('.kr-popin-button').length == 0) {
                        fullScreenLoader.startLoader();
                        me.isPlaceOrderActionAllowed(false);
                    }

                    me.submitEmbeddedForm();
                }
            },

            submitEmbeddedForm: function () {
                var me = this;
                var isSmartform = $('.kr-smart-form');
                var smartformModalButton = $('.kr-smart-form-modal-button');

                // If popin mode.
                if (me.getRestPopinMode() === "1") {
                    fullScreenLoader.stopLoader();
                    me.isPlaceOrderActionAllowed(true);

                    if (smartformModalButton.length === 0 && isSmartform.length > 0) {
                        var element = jQuery('.kr-smart-button');
                        var paymentMethod = element.attr('kr-payment-method');
                        KR.openPaymentMethod(paymentMethod);
                    } else {
                        KR.openPopin();
                    }
                } else {
                    if (isSmartform.length > 0 || smartformModalButton.length > 0) { // Smartform mode.
                        fullScreenLoader.stopLoader();
                        me.isPlaceOrderActionAllowed(true);
                        KR.openSelectedPaymentMethod();
                    } else { // Embedded mode.
                        KR.submit();
                    }
                }
            },

            refreshToken: function() {
                var me = this;
                CAN_REFRESH_TOKEN = false;
                fullScreenLoader.startLoader();

                storage.post(
                    url.build('payzen/payment_rest/token?payzen_action=refresh_token&form_key=' + $.mage.cookies.get('form_key'))
                ).done(function(response) {
                    if (response.token) {
                        if (me.getCompactMode() === "1") {
                            KR.setFormConfig({ cardForm: { layout: "compact" }, smartForm: { layout: "compact" }});
                        }

                        if (!me.getGroupThreshold() && !isNaN(me.getGroupThreshold())) {
                            KR.setFormConfig({ smartForm: { groupingThreshold: me.getGroupThreshold() }});
                        }

                        KR.setFormConfig({
                            formToken: response.token,
                            language: me.getLanguage(),
                            form: { smartform: { singlePaymentButton: { visibility: false }}}
                        }).then(
                            function(v) {
                                // Cart has changed, a new order will be placed.
                                PLACE_ORDER = true;
                                CAN_REFRESH_TOKEN = true;

                                KR.onFormReady(() => {
                                    me.hideSmartformPopinButton();
                                });
                            }
                        );
                    } else {
                        // Should not happen, this case is managed by failure callback.
                        console.log('Empty form token returned by refresh.');
                    }

                    fullScreenLoader.stopLoader();
                })
            },

            setToken: function() {
                var me = this;

                return new Promise((resolve, reject) => {
                    storage.post(
                        url.build('payzen/payment_rest/token?payzen_action=set_token&form_key=' + $.mage.cookies.get('form_key'))
                    ).done(function(response) {
                        if (response.token) {
                            if (me.getCompactMode() === "1") {
                                KR.setFormConfig({ cardForm: { layout: "compact" }, smartForm: { layout: "compact" }});
                            }

                            if (!me.getGroupThreshold() && !isNaN(me.getGroupThreshold())) {
                                KR.setFormConfig({ smartForm: { groupingThreshold: me.getGroupThreshold() }});
                            }

                            KR.setFormConfig({
                                formToken: response.token,
                                language: me.getLanguage(),
                                form: { smartform: { singlePaymentButton: { visibility: false }}}
                            }).then(
                                function(v) {
                                    resolve(response);
                                    let KR = v.KR;
                                    KR.onFocus(function(e) {
                                        $('#payzen_rest_form .kr-form-error').html('');
                                    });

                                    KR.onFormReady(() => {
                                        me.hideSmartformPopinButton();
                                    });

                                    KR.onError(function(e) {
                                        if (!e.metadata.hasOwnProperty('answer')) {
                                            return me.manageError(e);
                                        }

                                        var answer = e.metadata.answer;
                                        var data = {
                                            'kr-answer-type': 'V4/Payment',
                                            'kr-answer': JSON.stringify(answer.clientAnswer),
                                            'kr-hash': answer.hash,
                                            'kr-hash-algorithm': answer.hashAlgorithm
                                        };
    
                                        // Force redirection to response page if possibility of retries is exhausted.
                                        if (answer && (answer.clientAnswer.orderStatus == "UNPAID") && (answer.clientAnswer.orderCycle == "CLOSED")) {
                                            var form = $('<form></form>');
                                            form.attr("method", "post");
                                            form.attr("action", me.getRestReturnUrl());
    
                                            $.each(data, function(key, value) {
                                                var field = $('<input></input>');
    
                                                field.attr("type", "hidden");
                                                field.attr("name", key);
                                                field.attr("value", value);

                                                form.append(field);
                                            });

                                            KR.setFormConfig({ disabledForm: true }).then(
                                                function(e) {
                                                    $(document.body).append(form);
                                                    form.submit();
                                                }
                                            );
                                        } else if (me.updateOrder()) {
                                            // Ajax call to update order status.
                                            data['payzen_update_order'] = true;

                                            $.ajax({
                                                url: me.getRestReturnUrl(),
                                                type: "POST",
                                                data: data,
                                                success: function (resp) {
                                                   return me.manageError(e);
                                                }
                                            });
                                        } else {
                                            return me.manageError(e);
                                        }
                                    });

                                    KR.onSubmit(function(e) {
                                        customerData.invalidate(['cart']);
                                        return true;
                                    });

                                    me.submitEmbeddedForm();
                                }
                            ).catch(function () {
                                reject()
                            });
                        } else {
                            // Should not happen, this case is managed by failure callback.
                            console.log('Empty form token returned.');
                            reject(response)
                        }
                    }).fail(function(response) {
                        fullScreenLoader.stopLoader();
                        me.isPlaceOrderActionAllowed(true);
                        reject(response)
                    });
                });
            },

            checkTokenAmount: function(displayAmount, displayCurrency, baseAmount, baseCurrency, krAmount) {
                var me = this;

                storage.post(
                    url.build('payzen/payment_rest/token?payzen_action=get_token_amount_in_cents' + '&displayAmount=' + displayAmount + '&displayCurrency=' + displayCurrency + '&baseAmount=' + baseAmount + '&baseCurrency=' + baseCurrency + '&form_key=' + $.mage.cookies.get('form_key'))
                ).done(function(response) {
                    if (response.amountincents && krAmount && response.amountincents !== krAmount) {
                        // Refresh token since the amount in the embedded form is not up to date.
                        me.refreshToken();
                    }
                });
            },

            getPayload: function() {
                var me = this;

                return JSON.stringify({
                    cartId: quote.getQuoteId(),
                    totals: quote.getTotals(),
                    shippingAddress: quote.shippingAddress(),
                    shippingMethod: quote.shippingMethod(),
                    billingAddress: quote.billingAddress(),
                    paymentMethod: quote.paymentMethod(),
                    paymentData: me.getData()
                });
            },

            translateError: function(code) {
                var lang = this.getLanguage();
                var messages = ERROR_MESSAGES.hasOwnProperty(lang) ? ERROR_MESSAGES[lang] : ERROR_MESSAGES['en'];

                if (!messages.hasOwnProperty(code)) {
                    var index = code.lastIndexOf('_');
                    code = code.substring(0, index + 1) + '999';
                }

                return messages[code];
            },

            afterPlaceOrder: function() {
                var me = this;

                if (((me.getEntryMode() == 5) || (me.getEntryMode() == 6) || (me.getEntryMode() == 7)) && me.getRestFormToken()) {
                    // Embedded or popin mode.
                    $('#payzen_rest_form .kr-form-error').html('');

                    fullScreenLoader.startLoader();
                    me.isPlaceOrderActionAllowed(false);

                    (async () => {
                        await me.setToken().then(function() {
                            PLACE_ORDER = false;
                        }).catch(function(e) {
                            me.showErrorMessage()
                        });
                    })().catch(e => function() {
                        me.showErrorMessage();
                    });
                } else {
                    me._super();
                }
            },

            hideSmartformPopinButton: function() {
                var me = this;

                if (me.getRestPopinMode() === "1") {
                    var element = $(".kr-smart-form .kr-smart-form-wrapper.kr-type-popin .kr-smart-form-modal-button");
                    if (element.length > 0) {
                        element.hide();
                    }
                }
            }
        });
    }
);
