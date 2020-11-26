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
 * Custom renderer for the category mapping field.
 */
class CategoryMapping extends \Lyranetwork\Payzen\Block\Adminhtml\System\Config\Form\Field\FieldArray\ConfigFieldArray
{
    /**
     * @var \Lyranetwork\Payzen\Model\System\Config\Source\CategoryFactory
     */
    protected $payzenCategoryFactory;

    /**
     * @var \Magento\Catalog\Model\CategoryFactory
     */
    protected $categoryFactory;

    /**
     * @var bool
     */
    protected $staticTable = true;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Lyranetwork\Payzen\Model\System\Config\Source\CategoryFactory $payzenCategoryFactory
     * @param \Magento\Catalog\Model\CategoryFactory $categoryFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Lyranetwork\Payzen\Model\System\Config\Source\CategoryFactory $payzenCategoryFactory,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        array $data = []
    ) {
        $this->payzenCategoryFactory = $payzenCategoryFactory;
        $this->categoryFactory = $categoryFactory;

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
            'magento_category',
            [
                'label' => __('Magento category'),
                'style' => 'width: 200px;',
                'renderer' => $this->getLabelRenderer('_magentoCategory')
            ]
        );

        $options = [];
        foreach ($this->payzenCategoryFactory->create()->toOptionArray(true) as $option) {
            $options[$option['value']] = $option['label'];
        }

        $this->addColumn(
            'payzen_category',
            [
                'label' => __('PayZen category'),
                'style' => 'width: 200px;',
                'renderer' => $this->getListRenderer('_payzenCategory', $options)
            ]
        );

        parent::_prepareToRender();
    }

    /**
     * Obtain existing data from form element.
     * Each row will be instance of \Magento\Framework\DataObject
     *
     * @return array
     */
    public function getArrayRows()
    {
        $value = [];

        $allCategories = $this->getAllCategories();

        $savedCategories = $this->getElement()->getValue();
        if ($savedCategories && is_array($savedCategories) && ! empty($savedCategories)) {
            foreach ($savedCategories as $id => $category) {
                if (key_exists($category['code'], $allCategories)) {
                    // Update magento category name.
                    $category['magento_category'] = $allCategories[$category['code']];
                    $value[$id] = $category;

                    unset($allCategories[$category['code']]);
                }
            }
        }

        // Add not saved yet categories.
        if ($allCategories && is_array($allCategories) && ! empty($allCategories)) {
            foreach ($allCategories as $code => $name) {
                $value[uniqid('_' . $code . '_')] = [
                    'code' => $code,
                    'magento_category' => $name,
                    'payzen_category' => 'FOOD_AND_GROCERY',
                    'mark' => true
                ];
            }
        }

        $this->getElement()->setValue($value);
        return parent::getArrayRows();
    }

    private function getAllCategories()
    {
        $categories = $this->categoryFactory->create()
            ->getCollection()
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('id')
            ->addIsActiveFilter();

        if ($this->getElement()->getScope() === \Magento\Config\Block\System\Config\Form::SCOPE_STORES) {
            $rootId = $this->_storeManager->getStore($this->getElement()
                ->getScopeId())
                ->getRootCategoryId();
            $categories = $categories->addPathFilter("^1/$rootId/[0-9]+$");
        } else {
            $categories = $categories->addPathFilter("^1/[0-9]+/[0-9]+$");
        }

        $allCategories = [];
        foreach ($categories as $category) {
            $allCategories[$category->getId()] = $category->getName();
        }

        return $allCategories;
    }
}
