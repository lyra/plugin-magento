# PayZen for Magento 2

PayZen for Magento 2 is an open source plugin that links e-commerce websites based on Magento to PayZen secure payment gateway developed by [Lyra Network](https://www.lyra.com/).

Namely, it enables the following payment methods :
* PayZen - Standard payment
* [mutli] PayZen - Payment in installments
* [gift] PayZen - Gift card payment
* [choozeo] PayZen - Choozeo payment
* [fullcb] PayZen - Full CB payment
* [sepa] PayZen - SEPA payment
* [paypal] PayZen - PayPal payment

## Installation & upgrade

- Remove app/code/Lyranetwork/Payzen folder if already exists.
- Create a new app/code/Lyranetwork/Payzen folder.
- Unzip module in your Magento 2 app/code/Lyranetwork/Payzen folder.
- Open command line and change to Magento installation root directory.
- Enable module: php bin/magento module:enable --clear-static-content Lyranetwork_Payzen
- Upgrade database: php bin/magento setup:upgrade
- Re-run compile command: php bin/magento setup:di:compile
- Update static files by: php bin/magento setup:static-content:deploy [locale]

In order to deactivate the module: php bin/magento module:disable --clear-static-content Lyranetwork_Payzen

## Configuration

- In Magento 2 administration interface, browse to "STORES > Configuration" menu.
- Click on "Payment Methods" link under the "SALES" section.
- Expand PayZen payment method to enter your gateway credentials.
- Refresh invalidated Magento cache after config saved. 

## License

Each PayZen payment module source file included in this distribution is licensed under the Open Software License (OSL 3.0).

Please see LICENSE.txt for the full text of the OSL 3.0 license. It is also available through the world-wide-web at this URL: https://opensource.org/licenses/osl-3.0.php.