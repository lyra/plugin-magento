<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento 2. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
namespace Lyranetwork\Payzen\Block\Adminhtml\System\Config\Form\Field\Oney;

/**
 * Custom renderer for the oney payment options field.
 */
class OneyPaymentOptions extends \Lyranetwork\Payzen\Block\Adminhtml\System\Config\Form\Field\FieldArray\ConfigFieldArray
{
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
                'style' => 'width: 150px;'
            ]
        );
        $this->addColumn(
            'code',
            [
                'label' => __('Code '),
                'style' => 'width: 65px;'
            ]
        );
        $this->addColumn(
            'card_type',
            [
                'label' => __('Means of payment'),
                'class' => 'card_type',
                'renderer' => $this->getListRenderer('card_type', $this->getCardTypes())
            ]
        );
        $this->addColumn(
            'minimum',
            [
                'label' => __('Min. amount'),
                'style' => 'width: 80px;'
            ]
        );
        $this->addColumn(
            'maximum',
            [
                'label' => __('Max. amount'),
                'style' => 'width: 80px;'
            ]
        );
        $this->addColumn(
            'count',
            [
                'label' => __('Count '),
                'style' => 'width: 65px;'
            ]
        );
        $this->addColumn(
            'rate',
            [
                'label' => __('Rate '),
                'style' => 'width: 70px;'
            ]
        );

        parent::_prepareToRender();
    }

    protected function renderScript()
    {
        $script = parent::renderScript();

        $script .= "\n" . '
            <script>
                require([
                    "prototype"
                ], function () {
                    function getcountElt(name) {
                        var countName = name.replace("[card_type]", "[count]");
                        var countElt = $$("input[name=\"" + countName + "\"]")[0];
                        return countElt;
                    }

                    function updateCountElts() {
                        $$("select.card_type").each(function(elt) {
                            var countElt = getcountElt(elt.name);

                            if (elt.value === "ONEY_PAYLATER") {
                                countElt.disable();
                            } else {
                                countElt.enable();
                            }
                        });
                    }

                    function initFieldsAndObserver() {
                        $$("select.card_type").invoke("observe", "change", function() {
                            updateCountElts();
                        });
                    }

                    updateCountElts();
                    initFieldsAndObserver();

                    // Event when a row is added to the array of oney payment options.
                    $$("button.action-add").invoke("observe", "click", function() {
                        initFieldsAndObserver();
                    })
                })
            </script>';

        return $script;
    }

    public function getCardTypes()
    {
        return [
            'ONEY_3X_4X' => __('Payment in 3 or 4 times Oney'),
            'ONEY_10X_12X' => __('Payment in 10 or 12 times Oney'),
            'ONEY_PAYLATER' => 'Pay Later Oney'
        ];
    }

    public function getArrayRows()
    {
        // Get oney payment options.
        $savedOptions = $this->getElement()->getValue();
        if (! is_array($savedOptions)) {
            $savedOptions = [];
        }

        foreach ($savedOptions as $key => $option) {
            if (!isset($option["count"])) {
                $savedOptions[$key]["count"] = "";
            }
        }

        $this->getElement()->setValue($savedOptions);

        return parent::getArrayRows();
    }
}
