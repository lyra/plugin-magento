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

class Language implements \Magento\Framework\Option\ArrayInterface
{
    public function toOptionArray()
    {
        $options = [];

        foreach (\Lyranetwork\Payzen\Model\Api\PayzenApi::getSupportedLanguages() as $code => $name) {
            $options[] = [
                'value' => $code,
                'label' => __($name)
            ];
        }

        return $options;
    }
}
