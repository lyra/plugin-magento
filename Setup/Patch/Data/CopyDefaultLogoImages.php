<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento 2. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

namespace Lyranetwork\Payzen\Setup\Patch\Data;

use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Module\Dir\Reader as ModuleDirReader;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class CopyDefaultLogoImages implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    protected $moduleDataSetup;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var ModuleDirReader
     */
    protected $moduleDirReader;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param Filesystem $filesystem
     * @param ModuleDirReader $moduleDirReader
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        Filesystem $filesystem,
        ModuleDirReader $moduleDirReader
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->filesystem = $filesystem;
        $this->moduleDirReader = $moduleDirReader;
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $sourceDir = $this->moduleDirReader->getModuleDir('', 'Lyranetwork_Payzen') . '/view/base/web/images';
        if (! is_dir($sourceDir)) {
            $this->moduleDataSetup->getConnection()->endSetup();
            return $this;
        }

        $mediaDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $targetDir = $this->getUploadDir();
        $mediaDirectory->create($targetDir);

        foreach (scandir($sourceDir) as $sourceFile) {
            if ($sourceFile === '.' || $sourceFile === '..') {
                continue;
            }

            $sourcePath = $sourceDir . '/' . $sourceFile;
            if (! is_file($sourcePath)) {
                continue;
            }

            $destinationPath = $targetDir . '/' . $sourceFile;
            if ($mediaDirectory->isExist($destinationPath)) {
                continue;
            }

            $content = file_get_contents($sourcePath);
            if ($content === false) {
                continue;
            }

            $mediaDirectory->writeFile($destinationPath, $content);
        }

        $this->moduleDataSetup->getConnection()->endSetup();
        return $this;
    }

    /**
     * Keep the same upload directory as the logo backend model upload_dir config.
     *
     * @return string
     */
    protected function getUploadDir()
    {
        return 'payzen/images';
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }
}
