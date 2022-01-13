/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento 2. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list',
        'Magento_Checkout/js/view/payment/default'
    ],
    function(Component, rendererList) {
        'use strict';
        rendererList.push(
            {
                type: 'payzen_standard',
                component: 'Lyranetwork_Payzen/js/view/payment/method-renderer/payzen-standard'
            },
            {
                type: 'payzen_multi',
                component: 'Lyranetwork_Payzen/js/view/payment/method-renderer/payzen-multi'
            },
            {
                type: 'payzen_gift',
                component: 'Lyranetwork_Payzen/js/view/payment/method-renderer/payzen-gift'
            },
            {
                type: 'payzen_choozeo',
                component: 'Lyranetwork_Payzen/js/view/payment/method-renderer/payzen-choozeo'
            },
            {
                type: 'payzen_oney',
                component: 'Lyranetwork_Payzen/js/view/payment/method-renderer/payzen-oney'
            },
            {
                type: 'payzen_fullcb',
                component: 'Lyranetwork_Payzen/js/view/payment/method-renderer/payzen-fullcb'
            },
            {
                type: 'payzen_sepa',
                component: 'Lyranetwork_Payzen/js/view/payment/method-renderer/payzen-sepa'
            },
            {
                type: 'payzen_paypal',
                component: 'Lyranetwork_Payzen/js/view/payment/method-renderer/payzen-paypal'
            },
            {
                type: 'payzen_franfinance',
                component: 'Lyranetwork_Payzen/js/view/payment/method-renderer/payzen-franfinance'
            },
            {
                type: 'payzen_other',
                component: 'Lyranetwork_Payzen/js/view/payment/method-renderer/payzen-other'
            }
        );

        /** Add view logic here if needed */
        return Component.extend({});
    }
);
