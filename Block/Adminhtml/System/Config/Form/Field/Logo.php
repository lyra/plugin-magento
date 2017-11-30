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
 * Custom renderer for the PayZen sub-module logo.
 */
class Logo extends \Magento\Config\Block\System\Config\Form\Field
{

    /**
     *
     * @var \Lyranetwork\Payzen\Helper\Data
     */
    protected $dataHelper;

    /**
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Lyranetwork\Payzen\Helper\Data $dataHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Lyranetwork\Payzen\Helper\Data $dataHelper,
        $data = []
    ) {
        $this->dataHelper = $dataHelper;

        parent::__construct($context, $data);
    }

    /**
     * Set default image URL.
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $html = parent::render($element);

        $fileName = $element->getValue();
        if ($fileName && ! $this->dataHelper->isUploadFileImageExists($fileName)) {
            // default logo defined in the module
            $imgSrc = $this->getViewFileUrl('Lyranetwork_Payzen::images/' . $fileName);

            $html = preg_replace('#src="https?://[^"]+"#', 'src="' . $imgSrc . '"', $html);
        }

        return $html;
    }
}
