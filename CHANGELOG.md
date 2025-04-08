2.11.1, 2025-04-08:
- Bug fix: Fix error related to payment form token creation when order increment ID is empty.

2.11.0, 2025-03-31:
- Improve order retrieving process.
- Bug fix: Fix order identifier type casting on REST payment IPN and return to shop processing.
- Remove the iframe payment mode.
- Remove the payment by embedded fields legacy mode.
- Update list of supported payment means.
- Update list of supported currencies.

2.10.2, 2025-02-27:
- Bug fix: Fix compilation problem relative to class constructor parameters.

2.10.1, 2025-02-24:
- Bug fix: Fix processing notification URL on payment abandoned by buyer.
- Bug fix: Fix error related to refund processing when admin authentication information not available.
- [embedded] Bug fix: Fix error related to retrieving order increment ID.
- [embedded] Improve REST API errors management.
- Update list of supported payment means.
- Update list of supported currencies.

2.10.0, 2024-09-17:
- [embedded] Use customer wallet functionality to manage payment by alias on buyer account.
- [alias] Improve alias validity check.

2.9.2, 2024-07-30:
- Bug fix: Do not override CSP module configured mode.
- Some fixes by plugin variant.

2.9.1, 2024-07-01:
- [technical] Enabled some features by plugin variant.

2.9.0, 2024-06-21:
- Added possibility to configure currency used for payment processing.

2.8.8, 2024-05-30:
- Rollback using 500 http code on error for IPN calls.

2.8.7, 2024-05-10:
- Bug fix: Fix error 400 related to other payment means validation.
- Bug fix: Fix order info for email template.
- Bug fix: Fix payment means logos management.
- Bug fix: Fix refunding order issue in case of multi-currency Magento Shop.
- [embedded] Bug fix: Fix update order status on each payment attempt.
- Added new transaction status REFUND_TO_RETRY.

2.8.6, 2024-04-10:
- [embedded] Take into account the notification URL on payment abandoned by buyer.
- [smartform] Bug fix: Fix error related to display Smartform in a popin.

2.8.5, 2024-03-07:
- Bug fix: Fix errors related to the deprecation of the Framework Zend.

2.8.4, 2024-02-13:
- Improve IPN errors management.

2.8.3, 2024-01-03:
- Set return mode to POST by default.
- [technical] Improve features management by plugin variant.
- [embedded] Improve form token refresh.

2.8.2, 2023-11-29:
- Handle refund of a split payment.
- Improve refund error messages.
- Update list of supported payment means.
- Update list of supported currencies.

2.8.1, 2023-10-23:
- [smartform] Bug fix: Fix payment means filter issue with Smartform modes.
- [embedded] Bug fix: Fix error related to payment form validation on placing order.
- [oney] Added possibility to refund Oney payments from Magento Back Office via WS.
- Create credit memo on transaction total refund from gateway Back Office.

2.8.0, 2023-09-15:
- [smartform] Smartform integration.
- [oney] Added 10x 12x Oney and Paylater means of payment.
- [embedded] Bug fix: Fix form token update issue in Magento 2.4.5 and higher.
- Added possibility to capture order on invoice creation in Magento Back Office.
- Bug fix: Fix PHP error related to passing null parameter to str_replace function.

2.7.2, 2023-08-24:
- Added compatibility with PHP 8.2.x versions.
- Improve management of refunds from Magento backend.
- Update list of supported payment means.
- Update list of supported currencies.

2.7.1, 2023-06-09:
- [embedded] Bug fix: Fix error related to form token update after a failed payment attempt.
- [embedded] Bug fix: Fix error related to validation mode.
- Minor code fixes.

2.7.0, 2023-02-03:
- [embedded] Added possibility to update order status on each payment attempt.
- [embedded] Improve order and payment processing.
- Added field for online module documentation.
- Minor code fixes.

2.6.5, 2023-01-23:
- Bug fix: Fix payment in iframe mode when JavaScript minification is enabled in Magento.
- Update list of supported payment means.
- Update list of supported currencies.

2.6.4, 2022-11-24:
- [embedded] Bug fix: Fix error related to invalid email format when customer checkout as guest.
- Minor code fixes.

2.6.3, 2022-11-10:
- [embedded] Do not create payment token if quote data has not changed.
- [embedded] Workarround to avoid the extra retry when the number of attempts is reached.
- Minor code fixes.

2.6.2, 2022-10-17:
- Bug fix: Fix error related to number of times a coupon is used when payment is failed.
- Bug fix: Fix error related to CURLOPT_SSL_VERIFYHOST supported values in REST API.

2.6.1, 2022-09-14:
- [embedded] Bug fix: Fix embedded fields displaying.
- Minor code fixes.

2.6.0, 2022-09-08:
- [embedded] Possibility to enable payment by alias with embedded payment fields.
- Update list of supported payment means.
- Added Portuguese translation.

2.5.13, 2022-08-16:
- Allow module to be installed when using php 8.1 or higher.
- [oney] Update supported phone number format.

2.5.12, 2022-06-02:
- Bug fix: Fix session recovery issues on return page in Magento 2.4.x.
- Bug fix: Fix error when receiving IPN on cancellation calls.
- Add CSP configuration.
- Adapt code to PHP 8.x.
- Update list of supported payment means.

2.5.11, 2022-01-13:
- [embedded] Bug fix: Refresh minicart items count when payment is successful in Magento 2.4.x.
- [embedded] Bug fix: Validate Magento quote data before payment submit with REST API.
- [embedded] Update order summary and amount to pay with embedded payment fields when modifying minicart (workarround for a Magento bug).
- Fix session recovery issues with POST mode related to Samesite cookie in Magento 2.4.x.

2.5.10, 2021-10-05:
- [embedded] Bug fix: Do not refresh payment page automatically after an unrecoverable error.
- [embedded] Bug fix: Update payment token only if embedded payment is enabled.
- [embedded] Bug fix: Do not try to create form token if amount or currency are invalid.
- [embedded] Check standard submodule availability before creating form token.
- [oney] Bug fix: Do not check shipping country restriction for Oney payment in case of virtual quote.
- [franfinance] Send information about shipping method for Franfinance payment method.
- Dispatch restore_quote event when payment is not successful.
- Do not expand plugin configuration by default in Magento Back Office.

2.5.9, 2021-07-15:
- [embedded] Bug fix: Fix displayed installment amount in multi payment option.
- Display installments number in order details when it is available.

2.5.8, 2021-07-08:
- Display authorized amount in order details when it is available.

2.5.7, 2021-06-24:
- Send the relevant part of the current PHP version in vads_contrib field.
- Improve support e-mail display.

2.5.6, 2021-05-27:
- Possibility to open support issue from the plugin configuration panel or an order details page.
- Update 3DS management option description.
- Improve REST API keys configuration display.
- Improve plugin logs.

2.5.5, 2021-03-09:
- Use online payment means logos.
- [franfinance] Send cart details for Franfinance payments.

2.5.4, 2021-02-02:
- Fix installment details errors introduced in 2.5.3 version.

2.5.3, 2021-02-01:
- Fix installment information when saving the payment details in the Magento Back Office.
- Workarround to avoid conflict with "Payment & Shipping restrictions" plugin.

2.5.2, 2021-01-05:
- [embedded] Bug fix: Use the last version of PrototypeJS library when embedded payment fields option is enabled.
- Minor code fixes.

2.5.1, 2020-12-15:
- Display warning message on payment in iframe mode enabling.
- Bug fix: Manage PSP_100 errors when calling REST web services.
- Bug fix: Error 500 due to obsolete function (get_magic_quotes_gpc) in PHP 7.4.

2.5.0, 2020-11-25:
- [embedded] Bug fix: Empty cart to avoid double payments with REST API.
- [franfinance] Added new Franfinance submodule.
- [oney] Added payment in 3 or 4 times Oney submodule.
- [sepa] Possibility to enable payment by alias in SEPA submodule.
- [embedded] Possibility to display embedded payment fields in a popin.
- [alias] Added link to delete stored means of payment.
- [alias] Display the brand of the stored means of payment if payment by alias is enabled.
- [alias] Check alias validity before proceeding to payment.
- Possibility to configure REST API URLs.
- Refund payments using REST API v4.
- Accept and deny payments using REST API v4.
- Validate payments using REST API v4.
- [other] Possibility to propose other payment means by redirection.
- Improve configuration fields validation messages.
- Fix some translations.

2.4.11, 2020-11-02:
- [embedded] Bug fix: Display 3DS result for REST API payments.
- Bug fix: Do not re-create invoice if it already exists.
- Some minor fixes relative to configuration screen.

2.4.10, 2020-10-06:
- [fullcb] Bug fix: Error when trying to pay with Full CB if payment options selection is disabled.
- Update payment means list.

2.4.9, 2020-08-12:
- Bug fix: Error while trying to use WS services (accept, deny and validate payment, online refund).
- Update payment means list.

2.4.8, 2020-07-20:
- [embedded] Bug fix: Error due to strongAuthentication field renaming in REST token creation.
- [embedded] Bug fix: Do not cancel orders in status "Fraud suspected" when new failed IPN calls are made.
- Update payment means logos.
- Improve logged information.

2.4.7, 2020-06-19:
- [embedded] Bug fix: Amount did not include shipping fees when using embedded payment fields in some cases.
- [embedded] Bug fix: Compatibility of payment with embedded fields with Internet Explorer 11.
- [embedded] Bug fix: Error 500 due to riskControl modified format in REST response.
- Bug fix: Fix brand choice field management when returning to store for a payment with gift card.

2.4.6, 2020-05-12:
- Some minor fixes.
- [embedded] Bug fix: Use the correct return and private keys according to the plugin context mode.

2.4.5, 2020-04-23:
- Some minor fixes.
- [embedded] Bug fix: Load embedded payment fields JavaScript library inside require() function.

2.4.4, 2020-02-14:
- Bug fix: NoSuchEntityException occurs when trying to retrieve a removed product category.
- [embedded] Bug fix: Amount did not include shipping fees when using embedded payment fields if payment step is not refreshed.
- Bug fix: Payment information in order confirmation email was not correctly translated in some multistore cases.

2.4.3, 2020-01-20:
- Bug fix: Manage formKey for compatibility with Magento 2.3.x versions
- Bug fix: 3DS result is not correctly saved in Magento when using embedded payment fields.

2.4.2, 2019-08-08:
- Bug fix: Order increment ID not sent on some REST payments.
- Bug fix: Error at the end of the payment by REST API in guest mode.

2.4.1, 2019-07-01:
- Bug fix: wrong WSDL URL since v2.4.0.
- [SEPA] Save SEPA aliases separately from CB payment aliases.

2.4.0, 2019-06-17:
- Bug fix: consider UNDER_VERIFICATION as a success status for refund transactions.
- Fix IPN URL CSRF verification for compatibility with Magento 2.3.x versions.
- [embedded] Added payment with embedded fields option using REST API.
- [gift] Added Gift submodule.
- [fullcb] Added Full CB submodule.
- [paypal] Added PayPal submodule.
- [sepa] Added SEPA submodule.
- Possibility to enable payment by alias.
- Added backend buttons to refuse or accept orders in case of suspected fraud.
- Added backend button to validate payment of pending orders.
- Improve payment in iframe mode display.
- Possibility to not send cart data when not mandatory.
- Send Magento user name and IP to gateway for backend WS operations.
- Added specific error message for chargebacks refund.
- Fix some plugin translations.
- Do not send cart if it contains too much different items (more than 85).

2.3.2, 2018-12-24:
- Bug fix: get the correct means of payment when selection on site is enabled.
- [paypal] Bug fix: error when refunding a PayPal payment.
- Fix new signature algorithm name (HMAC-SHA-256).
- Send Magento phone number as vads\_cell\_phone (required for some payment means).
- Update payment means logos.
- Improve iframe mode interface.
- Save transaction UUID in order payment details.
- Added Spanish translation.
- [prodfaq] Fix notice about shifting the shop to production mode.
- Improve error message after a failed payment.

2.3.1, 2018-07-06:
- [shatwo] Enable HMAC-SHA-256 signature algorithm by default.
- Ignore spaces at the beginning and the end of certificates on return signature processing.

2.3.0, 2018-05-23:
- Enable signature algorithm selection (SHA-1 or HMAC-SHA-256).
- Improve backend configuration screen.

2.2.0, 2018-03-19:
- Display card brand user choice if any in backend order details.

2.1.4, 2018-03-08:
- Bug fix: Check value type in logo uploader to avoid a PHP warning during import data from configuration files.
- Bug fix: Added the component load order in etc/module.xml to avoid an exception during Magento installation using comand-line interface.
- [technical] Manage enabled/disabled features by plugin variant.

2.1.3, 2017-10-16:
- Compatibility with Magento 2.2 version (method signature, namespace use, JSON unserialize and layout init).

2.1.2, 2017-08-14:
- Bug fix: compilation problem relative to class constructor parameters.
- Possibility to view payment page within pop-in for standard payment (iframe mode).
- Possibility to select payment card type on merchant website (for both standard payment and payment in installments).
- Save both converted and original paid amounts in Magento transaction details.
- Use of protected variables (instead of private) to facilitate module code extension.
- [multi] Register details about all payment installments.

2.1.1, 2017-01-13:
- Bug fix: correction of an error when returning to store using browser backward button.
- [multi] Bug fix: selected payment in installments option is not considered since Magento 2.1.3.
- Update module structure and code to fulfill Magento marketplace requirements.
- Using "lyranetwork" as vendor name instead of "lyra" (already used in Magento Marketplace).
- [giropay] Upgrade supported card types list.

2.1.0, 2016-11-05:
- Bug fix: error relative to CMS version detection since Magento V 2.1.
- Bug fix: notify URL is not displayed correctly in module backend since Magento V 2.1.
- [multi] Bug fix: multiple payment submodule dos not work since Magento V 2.1 (selected payment option is not considered in frontend).
- Remove control over certificate format modified on the gateway.
- Possibility to refund payments from Magento backend via WS.
- Backend payments in MOTO mode.
- Upgrade supported card types list.

2.0.1, 2016-06-02:
- Bug fix: correction of an error in backend order creation page when module is enabled.
- Bug fix: use of \_scope parameter (instead of \_store) in store URLs to redirect to the correct store (in multistore mode).
- Bug fix: error occures when saving module settings without resfreshing cache (if asked by Magento).
- Bug fix: capture delay in submodules not considered if equals 0.
- [multi] Bug fix: payment in installments did not work (always processed as single).
- Dispatch event order\_cancel\_after after order cancellation.
- Check if current quote currency is supported before to check base currency.
- Improve logging system and log request format errors.
- Update german language file.
- Improve of label fields display on admin panel.
- [multi] Do not delete virtual multi payment methods (\_\_vads\_multi\_Nx) to avoid errors when viewing orders paid with these methods.

2.0.0, 2016-03-10:
- New PayZen payment module for magento 2.