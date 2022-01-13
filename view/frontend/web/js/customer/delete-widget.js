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
define([
    'jquery',
    'Magento_Ui/js/modal/modalToggle',
    'mage/translate'
], function($, modalToggle) {
    'use strict';

    return function(config, deleteButton) {
        config.buttons = [{
            text: $.mage.__('Cancel'),
            class: 'action secondary cancel'
        }, {
            text: $.mage.__('Delete'),
            class: 'action primary',

            /**
             * Default action on button click.
             */
            click: function(event) {
                $(deleteButton.form).submit();
            }
        }];

        modalToggle(config, deleteButton);
    };
});
