1.12.3, 2021-04-27:
- Update 3DS management option description.
- Improve REST API keys configuration display.

1.12.2, 2021-03-09:
- Fix installment information when saving the payment details in the Magento Back Office.
- Use online payment means logos.
- Fix payment table name in request when adding payment method column in Magento Back Office.
- Bug fix: Do not display payment card fields for payment from Magento Back Office.

1.12.1, 2020-12-09:
- Bug fix: Manage PSP_100 errors when calling REST web services.
- Bug fix: Error 500 due to obsolete function (get_magic_quotes_gpc) in PHP 7.4.

1.12.0, 2020-12-02:
- Reorganize Magento Back Office (REST API keys moved to general configuration section).
- Do payment from Magento Back Office by redirection in all cases.
- Refund payment using REST API.
- Payment validation using REST API.
- Accept and deny payment using REST API.
- [embedded] Possibility to display embedded payment fields in a popin.
- Do not use vads_order_info* gateway parameter.
- [alias] Display the brand of the registered means of payment in payment by alias.
- [alias] Check registred alias Validity before proposing it for payment.
- [alias] Added possibility to delete registered payment means.

1.11.4, 2020-11-06:
- [embedded] Bug fix: Force redirection when there is an error in payment form token creation.
- [embedded] Bug fix: Display 3DS results for REST API payments.
- [embedded] Bug fix: Empty cart to avoid double payments with REST API.
- [embedded] Bug fix: Embedded payment fields not correctly displayed since the last gateway JS library delivery.
- Bug fix: Do not re-create invoice if it already exists.
- Some minor fixes relative to plugin configuration update.

1.11.3, 2020-10-14:
- [oney] Do not display payment installments for buyer.
- Update payment means list.

1.11.2, 2020-08-19:
- [embedded] Bug fix: Do not cancel orders in status "Fraud suspected" when new IPN calls are made.
- [embedded] Bug fix: Error due to strongAuthenticationState field renaming in REST token creation.
- [embedded] Bug fix: Compatibility of payment with embedded fields with Internet Explorer 11.
- [embedded] Bug fix: Error 500 due to riskControl modified format in REST response.
- [oney] Bug fix: Fix Oney3x4x options requirement.
- [oney] Make phone number mandatory for Oney 3x/4x payments.
- Update payment means logos.

1.11.1, 2020-04-29:
- [embedded] Bugfix: Payment fields error relative to new JavaScript client library.
- [embedded] Disable 1-Click payment buttons when using payment with embedded fields.
- [sofort] Bug fix: do not cancel order on buyer return if transaction status is NOT_CREATED.
- Improve some plugin translations.

1.11.0, 2020-03-04:
- [oney] Adding 3x 4x Oney means of payment as submodule.
- Do not consider CAPTURE_FAILED as a success status.
- Improve payment statuses management.

1.10.3, 2020-02-19:
- [embedded] Bug fix: payment data was not refreshed in some cases with embedded payment fields.
- [iframe] Fix compatibility with OneStepCheckout plugin (error when checking enabled mode).

1.10.2, 2020-01-22:
- [embedded] Bug fix: 3DS result is not correctly saved in Magento when using embedded payment fields.
- [embedded] Bug fix: fix capture delay parameter name when using embedded payment fields.

1.10.1, 2019-11-18:
- Bug fix: send region code instead of label when it is significant.
- [embedded] Bug fix: default placeholders for embedded fields not translated with material theme.
- [embedded] Bug fix: currency and effective currency fields are inverted in REST API response.
- Bug fix: use correct class name for SOAP WS API classes.
- Disabled other payment means for Magento Back Office payment.

1.10.0, 2019-08-28:
- Bug fix: Accept UNDER_VERIFICATION transaction status for refunds.
- Use Lyranetwork as developer namespace instead of Lyra.
- [embedded] Added payment with embedded fields option using REST API.
- [embedded] Force the use of the last version of PrototypeJS lib.
- Removed feature card data acquisition on merchant website.
- Possibility to not send cart data when not mandatory.
- Possibility to propose various payment means by redirection.
- [sepa] Save SEPA tokens separately from CB payment tokens.

1.9.4, 2019-05-20:
- Bug fix: regression relative to currency check management.

1.9.3, 2019-05-13:
- Bug fix: fatal error on order view occurring when there is a format error in payment form.
- Bug fix: translations broken and performance decrease.
- Improve code relative to currency check to avoid conflict with One Page Checkout plugin.
- Send Magento username and IP to gateway for Magento Back Office WS operations.
- Do not send cart data if it contains more than 85 different items.
- Fix some plugin translations.
- Added specific error message for chargebacks refund.

1.9.2, 2018-12-24:
- [multi] Bug fix: error in first installment amount calculation when saving payment in installments details.
- [sepa] Bug fix: update country list for submodule SEPA.
- [sepa] Bug fix: redirection to error page after a successful payment.
- [paypal] Bug fix: error when refunding a PayPal payment.
- Fix new signature algorithm name (HMAC-SHA-256).
- Send Magento phone number as vads\_cell\_phone (required for some payment means).
- Possibility to validate *all* transactions of a payment in installments from Magento Back Office.
- Save transaction UUID in order payment details.
- Improve "Shipping options" configuration field management.
- Added Spanish translation.
- [prodfaq] Fix notice about shifting the shop to production mode.
- Comply with the new management of REGISTER feature on gateway.
- Improve error message after a failed payment.

1.9.1, 2018-07-06:
- Bug fix: Fixed setTitle() function name in payment from Magento Back Office WS call.
- [shatwo] Enable HMAC-SHA-256 signature algorithm by default.
- Ignore spaces at the beginning and the end of certificates on return signature processing.

1.9.0, 2018-05-23:
- Bug fix: fix error during payment from Magento Back Office caused by clearing quote data.
- Bug fix: relative to reloading "Shipping method" field in 1-Click component.
- Bug fix: consider 3 DS options in all payment submodules.
- Bug fix: correct function name in WS call when paying from Magento Back Office.
- Enable signature algorithm selection (SHA-1 or HMAC-SHA-256).
- [fullcb] Adding Full CB means of payment as submodule.
- [sepa] Adding SEPA means of payment as submodule.
- [sofort] Update SOFORT Banking payment logo.
- Improve Magento Back Office configuration screen.
- [sofort] New "Pending funds transfer" order status for SEPA and SOFORT payments.

1.8.0, 2017-09-25:
- [oney] Bug fix: correct simulated FacilyPay Oney funding fees calculation.
- Bug fix: correctly translate risk assessment results.
- Bug fix: fix shipping amount displayed in 1-Click shipping widget.
- Bug fix: restore delivery fees when canceling payment.
- Improve some text translations.
- Save both effective and converted amounts in Magento transaction details.
- Consider Magento SUPEE-9767 security patch relative to upload fields.
- Hide masked card number in frontend order details.
- Add a confirmation message before module configuration reset.
- Add V PAY card type.
- Disable Internet banking and redirection methods for Magento Back Office orders.
- Display masked card number to identify card to be used in 1-Click payment.
- Set vads_action_mode to "IFRAME" when iframe mode is enabled (no longer use vads_theme_config).
- Only display payment cards that allow manual payment for Magento Back Office payment.
- Add help link on CVV field.
- Display card brand user choice if any in Magento Back Office order details.
- Restrict payment submodules to specific currencies.
- [cofinoga] Remove obsolete 3x CB Cofinoga means of payment.
- Empty cart after a successful payment when 3 steps checkout module is used.

1.7.1, 2017-04-03:
- Bug fix: relative to category and product loading when generating HTML form.

1.7.0, 2017-01-12:
- Bug fix: when 1-Click is enabled, the payment from Magento Back Office was sending REGISTER_PAY as action (instead of PAYMENT).
- Bug fix: cart is emptied after a failed payment in guest mode.
- [oney] Bug fix: FacilyPay Oney method is not available when user is disconnected.
- Bug fix: when iframe mode is enabled, payment page is displayed in iframe inside a blank page for 1-Click payments.
- Bug fix: optimize collection loading to avoid memory overflow errors.
- Bug fix: do not send order confirmation by e-mail if "Email Order Confirmation" option is unchecked (for Magento Back Office orders only).
- Bug fix: when iframe mode is enabled, payment page is displayed in iframe inside a blank page in Magento 1.4.x versions.
- Remove control over certificate format modified on the gateway.
- Adding "PRIORITY" as a possible value for "Rapidity" field in shipping options configuration.
- Adding field "Delay" to shipping options configuration.
- Use "Processing" as label for "Fraud Suspected" order status in frontend context.
- [oney] Do not check FacilyPay Oney data for Gift card payment submodule.
- [oney] FacilyPay Oney is now available for DOM. Merchant can configure allowed countries in module Magento Back Office.
- [oney] Compliance with Modial Relay shipping module when using FacilyPay Oney method.
- [oney] Display FacilyPay Oney payment review in checkout process and cap Oney fees as requested by Oney.
- [multi] Display all transactions in Magento Back Office order detail view for payment in installments.
- Reorganize module Magento Back Office options.
- Only load parent categories to associate them with PayZen categories in module configuration.
- Possibility to disable 3DS for each customer group.
- [multi] Possibility to enable card type selection on merchant website for payment in installments submodule.
- [ideal] Adding iDEAL means of payment as submodule.
- [sofort] Remove the useless PAYMENT PAGE section from SOFORT Banking submodule configuration.
- Upgrade Web Services from v3 to v5.
- Use new MODE_IFRAME=true gateway feature instead of CSS customize to enable iframe mode.
- [sofort] Adding SOFORT Banking means of payment as submodule.
- Improve payment information display in frontend order view.

1.6.2, 2016-06-01:
- Bug fix: problem with "Payment Method" column in orders grid added by the module.
- [giropay] Bug fix: problem with Giropay payment redirection from Magento Back Office.
- Improve of english and german translations.
- Improve of label fields display on admin panel.
- Do not post disabled and hidden settings to improve performance.
- [multi] Do not delete virtual multi payment methods (payzen_multi_Nx) to avoid errors when viewing orders paid with these methods.

1.6.1, 2016-04-05:
- Bug fix: override of Mage_Sales_Model_Order model to avoid a problem with payment_review order statuses (as with fraud suspected payments) in EE.
- Bug fix: refund error when capture delay is empty or equals to 0.
- Dispatch event order_cancel_after after order cancellation.
- New option available for "Card data entry mode": Payment page integrated to checkout process (inside iframe).
- Possibility to specify a CSS (and other theme configuration) to apply to payment page when payment in iframe is used.
- Deletion of "Re-fill cart on failure" setting. Cart is now automatically recovered after a failed payment.
- Adding a warning message if number of configuration settings is bigger than the limit defined in PHP configuration (php.ini).
- Checking order (selected) currency availability before checking store basic currency.
- [giropay] Adding Giropay means of payment as submodule.

1.6.0, 2015-10-28:
- Bug fix: consider the store ID chosen for payment from Magento Back Office.
- 1-Click payment (require PayZen payment by identifier option).
- [oney] Ability to choose (force) FacilyPay Oney payment option from Magento frontend.
- Adding product category to product label sent to gateway (when shopping cart data are sent).
- Possibility to configure capture delay and validation mode in submodules.
- Management of risk assessment module (saving module results, accept / deny transactions from Magento Back Office).
- [postfinance] Adding PostFinance means of payment as submodule.

1.5.4, 2015-07-13:
- Bug fix: correction of SSL use check before allowing activation of card data entry on merchant website.
- Bug fix: some options stay available even they are disabled in chosen scope.
- Bug fix: downloadable products were accessible for suspected fraud orders (Magento 1.4 versions).
- Bug fix: detection of fraud suspicion is now based on vads_risk_control field instead of vads_extra_result.
- Bug fix: right alignment of method logos in checkout page.
- Correction of PT and DE payment results translations.
- After PayZen v2.4 delivery, amounts are now not automatically wrongly checked. So, cart data are again sent for all submodules.
- Tax amount and delivery fees are again sent to gateway for PayPal payments.
- [oney] Product labels are modified according to FacilyPay Oney regex before redirection to the gateway to make module configuration easier.
- [oney] Consideration of Magento configuration scope for delivery options in the module admin panel.
- Consideration of Magento configuration scope for product categories in the module admin panel.
- [sofort] Ability to choose many countries in SOFORT Banking submodule configuration.
- Saving the results of risk controls in order details.
- Ability to accept or deny orders when fraud suspected from Magento Back Office.
- Adding EN translations for gateway responses.
- Dynamic translation of gateway responses in order details.
- Specific notice message about notification URL in maintenance mode.

1.5.3, 2015-05-20:
- Bug fix: the use of discount coupons produces a form error (108 – TAX_AMOUNT). As a workarround, cart data are not sent to the gateway.
- Bug fix: when many payment methods are associated to one order (as via MDN module), order grid could not be shown. So the module displays the first payment method for each order.

1.5.2, 2015-04-02:
- Bug fix: deletion of omitted code used for test (that creates 300 product categories).
- Bug fix: saving virtual orders correctly in "Complete" status.
- Bug fix: the use of discount coupons produces a form error (108 – TAX_AMOUNT). Discounts are now applied to products prices and/or to delivery fees according to Magento promotion configuration.

1.5.1, 2015-03-02:
- Consideration of status UNDER_VERIFICATION for PayPal transactions.

1.5, 2015-02-18:
- Bug fix: for failed / cancelled orders, shopping cart was duplicated in database.
- Bug fix: in multistore mode, shopping cart was emptied for failed / cancelled orders (except in main store).
- Bug fix: cart items were not loaded correctly for Magento v 1.5 or lower (Fatal error: Call to a member function getCategoryIds() on a non-object).
- Bug fix: rounding problem causing difference between order total amount and the sum of cart items amounts.
- Displaying of payment methods labels instead of their codes in payment method column of Magento Back Office orders grid.
- [sofort] Adding SOFORT Banking means of payment as submodule.
- Ability to restrict different payment means by minimum / maximum amount for each customer group.

1.4.1, 2014-10-20:
- "ALL" is recovered as an option for available card types.
- [oney] Adding a checkbox to allow merchants to specifiy if they have FacilyPay Oney contract.
- Product categories configuration is moved to admin general configuration section.
- Upgrade of PayPal logo.

1.4, 2014-06-16:
- Bug fix: virtual orders were not automatically moved to "Processing" status.
- [oney] Adding FacilyPay Oney means of payment as submodule.
- [paypal] Adding PayPal means of payment as submodule.
- Ability to enable / disable module logs from module admin panel.
- Risk controls taken into account (suspected orders moved to "Fraud suspected" status).

1.3, 2013-12-03:
- [cofinoga] Adding 3x CB Cofinoga means of payment as submodule.
- Ability to pay Magento Back Office orders with this payment module.
- Ability to make refunds for payments generating one PayZen transaction.

1.2, 2013-04-23:
- Compatibility with PHP 5.
- Compliance with Zend and Magento standards.
- Compatibility with Magento CE from 1.4 to 1.7 versions.
- Taking into account of multi brand payments.
- [multi] Adding payment in installments submodule.
- [gift] Adding gift card submodule.
- Adding selective 3DS according to order amount.
- Ability to choose payment card in merchant website.
- Ability to enter card data in merchant website.
- Adding of module (re-)initialization button in admin panel.

1.1, 2012-08-09:
- Bug fix: correction of invoice generation to display items contained within grouped products.
- Modification of API files and class names.

1.0b, 2011-12-14:
- Update of field "accepted card types" (use of multiselect field instead of text field).

1.0a, 2011-12-09:
- Bug fix: deletion of check over delivery method for a virtual cart.

1.0, 2011-10-12:
- Initial version of the payment module compatible with Magento 1.4 or higher.