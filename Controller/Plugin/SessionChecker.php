<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento 2. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
namespace Lyranetwork\Payzen\Controller\Plugin;

/**
 * Class: SessionChecker
 *
 * Exclude the return URL that our plugin is using to POST back the data to Magento.
 */
class SessionChecker
{
    /**
     * Array
     */
    const PAYMENT_RETURN_PATHS = [
        'payzen/payment/response'
    ];

    /**
     * @var \Magento\Framework\App\Request\Http
     */
    private $request;

    /**
     * @param \Magento\Framework\App\Request\Http $request
     */
    public function __construct(\Magento\Framework\App\Request\Http $request)
    {
        $this->request = $request;
    }
    /**
     * Check if session can be started or not.
     * @param \Magento\Framework\Session\SessionStartChecker $subject
     * @param bool $result
     * @return bool
     */
    public function afterCheck(\Magento\Framework\Session\SessionStartChecker $subject, bool $result) : bool
    {
        if ($result === false) {
            return false;
        }

        if ($this->request->getFrontName() === 'payzen') {
            foreach (self::PAYMENT_RETURN_PATHS as $path) {
                if (strpos((string)$this->request->getPathInfo(), $path) !== false) {
                    return false;
                }
            }
        }

        return true;
    }
}
