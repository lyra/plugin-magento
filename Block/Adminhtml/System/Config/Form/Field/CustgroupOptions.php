<?php
/**
 * PayZen V2-Payment Module version 2.1.3 for Magento 2.x. Support contact : support@payzen.eu.
 *
 * NOTICE OF LICENSE
 *
 * This source file is licensed under the Open Software License version 3.0
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/osl-3.0.php
 *
 * @author    Lyra Network (http://www.lyra-network.com/)
 * @copyright 2014-2017 Lyra Network and contributors
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @category  payment
 * @package   payzen
 */
namespace Lyranetwork\Payzen\Block\Adminhtml\System\Config\Form\Field;

/**
 * Custom renderer for the PayZen FacilyPay Oney shipping options field.
 */
class CustgroupOptions extends \Lyranetwork\Payzen\Block\Adminhtml\System\Config\Form\Field\FieldArray\ConfigFieldArray
{

    /**
     *
     * @var \Magento\Customer\Model\GroupFactory
     */
    protected $customerGroupFactory;

    /**
     *
     * @var bool
     */
    protected $staticTable = true;

    /**
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Customer\Model\GroupFactory $customerGroupFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Customer\Model\GroupFactory $customerGroupFactory,
        array $data = []
    ) {
        $this->customerGroupFactory = $customerGroupFactory;

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
            'title',
            [
                'label' => __('Customer group'),
                'style' => 'width: 200px;',
                'renderer' => $this->getLabelRenderer('_title')
            ]
        );
        $this->addColumn(
            'amount_min',
            [
                'label' => __('Minimum amount'),
                'style' => 'width: 160px;'
            ]
        );
        $this->addColumn(
            'amount_max',
            [
                'label' => __('Maximum amount'),
                'style' => 'width: 160px;'
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
        $groups = $this->getAllCustomerGroups();

        $savedGroups = $this->getElement()->getValue();
        if (! is_array($savedGroups)) {
            $savedGroups = [];
        }

        if (! empty($savedGroups)) {
            foreach ($savedGroups as $id => $savedGroup) {
                if (key_exists($savedGroup['code'], $groups)) {
                    // refresh group title
                    $savedGroups[$id]['title'] = $groups[$savedGroup['code']];
                    if ($savedGroup['code'] === 'all') {
                        $savedGroups[$id]['all'] = true;
                    }

                    unset($groups[$savedGroup['code']]);
                }
            }
        }

        // add not saved yet groups
        foreach ($groups as $code => $title) {
            $group = [
                'code' => $code,
                'title' => $title,
                'amount_min' => '',
                'amount_max' => ''
            ];

            if ($code === 'all') {
                // add all groups entry
                $group['all'] = true;
                $savedGroups = array_merge([
                    uniqid('_all_') => $group
                ], $savedGroups);
            } else {
                $savedGroups[uniqid('_' . $code . '_')] = $group;
            }
        }

        $this->getElement()->setValue($savedGroups);
        return parent::getArrayRows();
    }

    private function getAllCustomerGroups()
    {
        $options = [];
        $options['all'] = __('ALL GROUPS');

        $groups = $this->customerGroupFactory->create()->getCollection();
        foreach ($groups as $group) {
            $options[$group->getCustomerGroupId()] = $group->getCustomerGroupCode();
        }

        return $options;
    }
}
