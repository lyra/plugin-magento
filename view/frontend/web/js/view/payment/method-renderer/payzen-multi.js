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
        'Lyranetwork_Payzen/js/view/payment/method-renderer/payzen-abstract'
    ],
    function (Component) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Lyranetwork_Payzen/payment/payzen-multi',
                payzenMultiOption: window.checkoutConfig.payment.payzen_multi.availableOptions[0]['key'],
                payzenCcType: window.checkoutConfig.payment.payzen_multi.availableCcTypes ?
                    window.checkoutConfig.payment.payzen_multi.availableCcTypes[0]['value'] : null
            },

            initObservable: function () {
                this._super().observe('payzenMultiOption');
                return this;
            },

            getData: function () {
                var data = this._super();
                data['additional_data']['payzen_multi_option'] = this.payzenMultiOption();

                return data;
            },

            getAvailableOptions: function () {
                return window.checkoutConfig.payment.payzen_multi.availableOptions;
            },

            showLabel: function () {
                return true;
            }
        });
    }
);