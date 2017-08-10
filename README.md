# PayZen for Magento

PayZen for Magento is an open source plugin that links e-commerce websites based on Magento to PayZen secured payment gateway developped by [Lyra Network](https://www.lyra-network.com/).

Namely, it enables the following payment methods :
* PayZen - Standard credit card payment
* PayZen - Credit card payment in installments

# Installation & Upgrade

1 - Unzip module in your Magento 2 app/code/Lyranetwork/Payzen folder
2 - Enable module: bin/magento module:enable --clear-static-content Lyranetwork_Payzen
3 - Upgrade database: bin/magento setup:upgrade
4 - Re-run compile command: bin/magento setup:di:compile

In order to deactivate the module: bin/magento module:disable --clear-static-content Lyranetwork_Payzen
In order to update static files: bin/magento setup:static-content:deploy [locale]

# Configuration

Coming soon.

## License

Each PayZen payment module source file included in this distribution is licensed under Open Software License (OSL 3.0).

Please see LICENSE.txt for the full text of the OSL 3.0 license. It is also available through the world-wide-web at this URL: https://opensource.org/licenses/osl-3.0.php.
