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
 * Custom renderer for the add gift cards field.
 */
class AddedPaymentMeans extends \Lyranetwork\Payzen\Block\Adminhtml\System\Config\Form\Field\FieldArray\ConfigFieldArray
{
    /**
     * Prepare to render.
     *
     * @return void
     */
    public function _prepareToRender()
    {
        $this->addColumn(
            'meanCode',
            [
                'label' => __('Code '),
                'style' => 'width: 100px;'
            ]
        );
        $this->addColumn(
            'meanName',
            [
                'label' => __('Label '),
                'style' => 'width: 180px;'
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
        $supportedCards = \Lyranetwork\Payzen\Model\Api\Form\Api::getSupportedCardTypes();

        // Get Added payment means.
        $addedCards = $this->getElement()->getValue();
        if (! is_array($addedCards)) {
            $addedCards = [];
        }

        foreach ($addedCards as $key => $card) {
            if (isset($supportedCards[$card['meanCode']])) {
                unset($addedCards[$key]);
            }
        }

        $this->getElement()->setValue($addedCards);
        return parent::getArrayRows();
    }
}
