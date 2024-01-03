<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento 2. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
namespace Lyranetwork\Payzen\Block\Adminhtml\System\Config\Form\Field;

/**
 * Custom renderer for the Customer data field.
 */
class CustomerData extends \Lyranetwork\Payzen\Block\Adminhtml\System\Config\Form\Field\FieldArray\ConfigFieldArray
{
    /**
     * @var \Magento\Customer\Model\ResourceModel\Attribute\CollectionFactory
     */
    protected $customerAttributeCollectionFactory;

    /**
     * @var \Magento\Customer\Model\ResourceModel\Address\Attribute\CollectionFactory
     */
    protected $customerAddressAttributeCollectionFactory;

    /**
     * @var bool
     */
    protected $staticTable = true;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\ObjectManagerInterface
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Customer\Model\ResourceModel\Attribute\CollectionFactory $customerAttributeCollectionFactory,
        \Magento\Customer\Model\ResourceModel\Address\Attribute\CollectionFactory $customerAddressAttributeCollectionFactory,
        array $data = []
    ) {
        $this->customerAttributeCollectionFactory = $customerAttributeCollectionFactory;
        $this->customerAddressAttributeCollectionFactory = $customerAddressAttributeCollectionFactory;
        parent::__construct($context, $data);
    }

    /**
     * Do not display if feature is not active.
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        // Get configured features.
        $features = \Lyranetwork\Payzen\Helper\Data::$pluginFeatures;
        if (! $features['brazil']) {
            return '';
        }

        return parent::render($element);
    }

    /**
     * Prepare elements to render.
     *
     * @return void
     */
    protected function _prepareToRender()
    {
        $this->addColumn(
            'data',
            [
                'label' => __('Data'),
                'style' => 'width: 200px;',
                'renderer' => $this->getLabelRenderer('_customerdata')
            ]
        );

        $options = $this->getAllFields();
        $this->addColumn(
            'field',
            [
                'label' => __('Magento field ID'),
                'style' => 'width: 200px;',
                'class' => 'payzen_field_list_type',
                'renderer' => $this->getListRenderer(
                    '_field',
                    $options
                )
            ]
        );

        parent::_prepareToRender();
    }

    /**
     * Obtain existing data from form element.
     * Each row will be instance of \Magento\Framework\DataObject.
     *
     * @return array
     */
    public function getArrayRows()
    {
        $value = [];

        $defaultFields = [
            'cpf' => __('CPF/CNPJ'),
            'district' => __('Address number'),
            'street' => __('Neighborhood')
        ];

        $savedFields = $this->getElement()->getValue();
        if ($savedFields && is_array($savedFields) && ! empty($savedFields)) {
            foreach ($savedFields as $code => $field) {
                if (key_exists($code, $defaultFields)) {
                    $field['code'] = $code;
                    $field['data'] = $defaultFields[$code];

                    $value[$code] = $field;

                    unset($defaultFields[$code]);
                }
            }
        }

        // Add not saved yet methods.
        if ($defaultFields && is_array($defaultFields) && ! empty($defaultFields)) {
            foreach ($defaultFields as $code => $name) {
                $value[$code] = [
                    'code' => $code,
                    'data' => $name,
                    'field' => ''
                ];
            }
        }

        $this->getElement()->setValue($value);
        return parent::getArrayRows();
    }

    private function getAllFields()
    {
        $attributesArrays = ['' => ''];

        $customerAttributes = $this->customerAttributeCollectionFactory->create();
        foreach ($customerAttributes as $attribute) {
            $attributesArrays['customer_' . $attribute->getAttributeCode()] = 'customer_entity.' . $attribute->getAttributeCode();
        }

        $customerAddressAttributes = $this->customerAddressAttributeCollectionFactory->create();
        foreach ($customerAddressAttributes as $attribute) {
            $attributesArrays['address_' . $attribute->getAttributeCode()] = 'customer_address_entity.' . $attribute->getAttributeCode();
        }

        return $attributesArrays;
    }
}
