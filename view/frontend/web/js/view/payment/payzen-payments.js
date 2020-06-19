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
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (Component, rendererList) {
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
            }
        );

        /** Add view logic here if needed */
        return Component.extend({});
    }
);
