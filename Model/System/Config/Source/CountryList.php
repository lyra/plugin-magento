<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento 2. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
namespace Lyranetwork\Payzen\Model\System\Config\Source;

class CountryList implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @var \Magento\Directory\Model\ResourceModel\Country\CollectionFactory
     */
    protected $_countryCollectionFactory;

    /**
     * @var \Magento\Framework\Locale\TranslatedLists
     */
    protected $translate;

    /**
     * @param \Magento\Directory\Model\ResourceModel\Country\CollectionFactory $countryCollectionFactory
     * @param \Magento\Framework\Locale\TranslatedLists $translate
     */
    public function __construct(
        \Magento\Directory\Model\ResourceModel\Country\CollectionFactory $countryCollectionFactory,
        \Magento\Framework\Locale\TranslatedLists $translate
    ) {
        $this->_countryCollectionFactory = $countryCollectionFactory;
        $this->translate = $translate;
    }

    public function toOptionArray()
    {
        $countries = $this->_countryCollectionFactory->create();

        $result = [];
        foreach ($countries as $country) {
            $code = $country->getCountryId();
            $name = $this->translate->getCountryTranslation($code);

            if (empty($name)) {
                $name = $code;
            }

            $result[$code] = $name;
        }

        return $result;
    }
}
