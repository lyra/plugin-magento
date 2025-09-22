<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento 2. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
namespace Lyranetwork\Payzen\Model\System\Config\Backend\Gift;

use Magento\Framework\App\Filesystem\DirectoryList;

class AddedCards extends \Lyranetwork\Payzen\Model\System\Config\Backend\Serialized\ArraySerialized\ConfigArraySerialized
{
    /**
     * @var \Magento\Framework\File\UploaderFactory
     */
    protected $uploaderFactory;

    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $filesystem;

    /**
     * @var \Magento\Config\Model\Config\Backend\File\RequestData\RequestDataInterface
     */
    protected $requestData;

    /**
     * @var \Magento\Framework\Image\AdapterFactory
     */
    protected $adapterFactory;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $config
     * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     * @param \Lyranetwork\Payzen\Helper\Data $dataHelper
     * @param \Magento\Framework\File\UploaderFactory $uploaderFactory
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Config\Model\Config\Backend\File\RequestData\RequestDataInterface $requestData
     * @param \Magento\Framework\Image\AdapterFactory $adapterFactory
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Lyranetwork\Payzen\Helper\Data $dataHelper,
        \Magento\Framework\File\UploaderFactory $uploaderFactory,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Config\Model\Config\Backend\File\RequestData\RequestDataInterface $requestData,
        \Magento\Framework\Image\AdapterFactory $adapterFactory,
        ?\Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        ?\Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->uploaderFactory = $uploaderFactory;
        $this->filesystem = $filesystem;
        $this->requestData = $requestData;
        $this->adapterFactory = $adapterFactory;

        parent::__construct(
            $context,
            $registry,
            $config,
            $cacheTypeList,
            $dataHelper,
            $resource,
            $resourceCollection,
            $data
        );
    }

    /**
     * Save uploaded files before saving config value.
     */
    public function beforeSave()
    {
        $value = $this->getValue();

        if (! is_array($value) || empty($value)) {
            $this->setValue([]);
            return parent::beforeSave();
        }

        $i = 0;
        foreach ($value as $key => $card) {
            $i++;

            if (empty($card)) {
                continue;
            }

            $this->checkCode($card['code'], $i);
            $this->checkName($card['name'], $i);

            $uploadDir = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA)->getAbsolutePath('payzen/images/cc/');

            // Load latest logo value.
            if ($this->dataHelper->fileExists($uploadDir . strtolower($card['code']) . '.png')) {
                $value[$key]['logo'] = strtolower($card['code']) . '.png';
            } else {
                $value[$key]['logo'] = false;
            }

            // Process file upload.
            $name = $card['logo']['name'];
            $tmpName = $card['logo']['tmp_name'];

            if ($name && $tmpName) { // Is there any file uploaded for the current card.
                $file = [];
                $file['tmp_name'] = $tmpName;
                $file['name'] = $name;

                try {
                    $uploader = $this->uploaderFactory->create(['fileId' => $file]);
                    $uploader->setAllowedExtensions([
                        'png'
                    ]);
                    $uploader->setAllowRenameFiles(false);
                    $uploader->setAllowCreateFolders(true);
                    $uploader->addValidateCallback('gift_card_logo', $this->adapterFactory->create(), 'validateUploadFile');

                    $result = $uploader->save($uploadDir, strtolower($card['code']) . '.png');

                    if (key_exists('file', $result) && ! empty($result['file'])) {
                        $value[$key]['logo'] = $result['file'];
                    }
                } catch (\Exception $e) {
                    // Upload errors.
                    $this->throwException('Card logo', $i, $e->getMessage());
                }
            }
        }

        $this->setValue($value);

        return parent::beforeSave();
    }

    private function checkCode($value, $i)
    {
        if (empty($value) || ! preg_match('#^[A-Za-z0-9\-_]+$#', $value)) {
            $this->throwException('Card code', $i);
        }
    }

    private function checkName($value, $i)
    {
        if (! preg_match('#^[^<>]*$#', $value)) {
            $this->throwException('Card name', $i);
        }
    }
}
