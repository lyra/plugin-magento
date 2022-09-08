<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento 2. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
namespace Lyranetwork\Payzen\Block\Adminhtml\System\Config\Form\Field\Other;

/**
 * Custom renderer for the other payment options field.
 */
class OtherPaymentMeans extends \Lyranetwork\Payzen\Block\Adminhtml\System\Config\Form\Field\FieldArray\ConfigFieldArray
{
    /**
     * @var \Magento\Directory\Model\ResourceModel\Country\CollectionFactory
     */
    protected $_countryCollectionFactory;

    /**
     * @var \Magento\Framework\Locale\TranslatedLists
     */
    protected $_translate;

    /**
     * @var \Lyranetwork\Payzen\Model\System\Config\Source\ValidationMode
     */
    protected $_validationModes;

    /**
     * @var \Lyranetwork\Payzen\Model\Method\Other
     */
    protected $method;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Directory\Model\ResourceModel\Country\CollectionFactory $countryCollectionFactory
     * @param \Lyranetwork\Payzen\Model\System\Config\Source\ValidationMode $validationModes
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Directory\Model\ResourceModel\Country\CollectionFactory $countryCollectionFactory,
        \Magento\Framework\Locale\TranslatedLists $translate,
        \Lyranetwork\Payzen\Model\System\Config\Source\ValidationMode $validationModes,
        \Lyranetwork\Payzen\Model\Method\Other $method,
        array $data = []
    ) {
        $this->_countryCollectionFactory = $countryCollectionFactory;
        $this->_translate = $translate;
        $this->_validationModes = $validationModes;
        $this->method = $method;

        parent::__construct($context, $data);
    }

    /**
     * Prepare to render.
     *
     * @return void
     */
    public function _prepareToRender()
    {
        $this->addColumn(
            'label',
            [
                'label' => __('Label '),
                'style' => 'width: 120px;'
            ]
        );

        // Get supported payment means including added ones.
        $cards = $this->method->getSupportedPaymentMeans();

        foreach ($cards as $key => $value) {
            $cards[$key] = $key . " - " . $value;
        }

        $this->addColumn(
            'means',
            [
                'label' => __('Means of payment'),
                'renderer' => $this->getListRenderer('means', $cards)
            ]
        );
        $this->addColumn(
            'minimum',
            [
                'label' => __('Min. amount'),
                'style' => 'width: 60px;'
            ]
        );
        $this->addColumn(
            'maximum',
            [
                'label' => __('Max. amount'),
                'style' => 'width: 60px;'
            ]
        );
        $this->addColumn(
            'countries',
            [
                'label' => __('Countries'),
                'style' => 'width: 120px;',
                'renderer' => $this->getMultiselectRenderer('countries', $this->getCountries())
            ]
        );
        $this->addColumn(
            'validation_mode',
            [
                'label' => __('Validation mode'),
                'style' => 'width: 120px;',
                'renderer' => $this->getListRenderer('validation_mode', $this->getValidationModes())
            ]
        );
        $this->addColumn(
            'capture_delay',
            [
                'label' => __('Capture delay column'),
            ]
        );
        $this->addColumn(
            'cart_data',
            [
                'label' => __('Cart data'),
                'renderer' => $this->getListRenderer('card_data', $this->yesno())
            ]
        );

        parent::_prepareToRender();
    }

    /**
     * Obtain existing data from form element.
     *
     * Each row will be instance of Varien_Object.
     *
     * @return array
     */
    public function getArrayRows()
    {
        $supportedCards = $this->method->getSupportedPaymentMeans();

        // Get other payment options.
        $savedOptions = $this->getElement()->getValue();
        if (! is_array($savedOptions)) {
            $savedOptions = [];
        }

        foreach ($savedOptions as $key => $option) {
            if (! isset($supportedCards[$option['means']])) {
                unset($savedOptions[$key]);
            }
        }

        $this->getElement()->setValue($savedOptions);
        return parent::getArrayRows();
    }

    public function getCountries()
    {
        $countries = $this->_countryCollectionFactory->create();

        $result = [];
        foreach ($countries as $country) {
            $code = $country->getCountryId();
            $name = $this->_translate->getCountryTranslation($code);

            if (empty($name)) {
                $name = $code;
            }

            $result[$code] = $name;
        }

        return $result;
    }

    public function getValidationModes()
    {
        $modes = [];
        foreach ($this->_validationModes->toOptionArray(true) as $option) {
            $modes[$option['value']] = $option['label'];
        }

        return $modes;
    }

    public function yesno()
    {
        return [
            '0' => __('No'),
            '1' => __('Yes')
        ];
    }
}
