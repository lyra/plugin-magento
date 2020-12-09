<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento 2. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

class Lyranetwork_Payzen_Model_RestException extends Exception
{
    protected $code;

    /**
     * @param message[optional]
     * @param code[optional]
     */
    public function __construct($message, $code = null)
    {
        parent::__construct($message, null);
        $this->code = $code;
    }
}
