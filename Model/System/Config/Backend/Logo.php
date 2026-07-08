<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento 2. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
namespace Lyranetwork\Payzen\Model\System\Config\Backend;

class Logo extends \Magento\Config\Model\Config\Backend\Image
{
    public function beforeSave()
    {
        $this->ensureUploadDirExists();

        $value = $this->getValue();
        if (is_array($value) && ! empty($value['name'])) {
            $value['value'] = $value['name'];
            $this->setValue($value);
        }

        parent::beforeSave();

        // Recover the last saved value.
        if (! $this->getValue() && empty($value['delete'])) {
            $this->setValue($this->getOldValue());
        }

        return $this;
    }

    /**
     * Prevent "Invalid file name" validator errors when media folder was removed intentionally.
     *
     * @return void
     */
    private function ensureUploadDirExists()
    {
        $uploadDir = $this->_getUploadDir();
        if (! $uploadDir) {
            return;
        }

        if (! $this->_mediaDirectory->isExist($uploadDir)) {
            $this->_mediaDirectory->create($uploadDir);
        }

        $moduleDirReader = \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Magento\Framework\Module\Dir\Reader::class);

        $sourceDir = $moduleDirReader->getModuleDir('', 'Lyranetwork_Payzen') . '/view/base/web/images';
        if (! is_dir($sourceDir)) {
            return;
        }

        foreach (scandir($sourceDir) as $sourceFile) {
            if ($sourceFile === '.' || $sourceFile === '..') {
                continue;
            }

            $sourcePath = $sourceDir . '/' . $sourceFile;
            if (! is_file($sourcePath)) {
                continue;
            }

            $destinationPath = $uploadDir . '/' . $sourceFile;
            if ($this->_mediaDirectory->isExist($destinationPath)) {
                continue;
            }

            $content = file_get_contents($sourcePath);
            if ($content === false) {
                continue;
            }

            $this->_mediaDirectory->writeFile($destinationPath, $content);
        }
    }
}
