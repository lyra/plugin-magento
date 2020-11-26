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
 * Class: CsrfValidator
 *
 * Bypass CSRF check for return and IPN URLs to avoid Form Key validation errors. Signature verification is used to check data integrity.
 * To insure backwards compatibility, CsrfAwareActionInterface cannot be used.
 */
class CsrfValidator
{
    /**
     * @param \Magento\Framework\App\Request\CsrfValidator $subject
     * @param \Closure $proceed
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Framework\App\ActionInterface $action
     */
    public function aroundValidate(
        $subject,
        \Closure $proceed,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\App\ActionInterface $action
    ) {
        $moduleName = $request->getModuleName();
        $controllerName = $request->getControllerName();
        $actionName = $request->getActionName();

        if ($moduleName === 'payzen' && in_array($controllerName, ['payment', 'payment_rest']) &&
            in_array($actionName, ['check', 'response'])) {
            return; // Skip CSRF check.
        }

        $proceed($request, $action); // Proceed Magento 2 core.
    }
}
