<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\EdgeDriver\Installer;

use Vaimo\EdgeDriver\Installer\PlatformAnalyser as OsDetector;

class PackageManager
{
    /**
     * @var \Vaimo\EdgeDriver\Installer\PlatformAnalyser
     */
    private $platformAnalyser;

    /**
     * @var \Vaimo\EdgeDriver\Installer\Utils
     */
    private $utils;

    /**
     * @var \Composer\Util\Filesystem
     */
    private $fileSystem;
    
    public function __construct() 
    {
        $this->platformAnalyser = new \Vaimo\EdgeDriver\Installer\PlatformAnalyser();
        $this->utils = new \Vaimo\EdgeDriver\Installer\Utils();
        $this->fileSystem = new \Composer\Util\Filesystem();
    }
    
    public function installBinaries(\Composer\Package\Package $package, $binDir)
    {
        $sourceDir = $package->getTargetDir();
        $sourceDir = file_exists(DIRECTORY_SEPARATOR . $sourceDir)
            ? (DIRECTORY_SEPARATOR . $sourceDir)
            : $sourceDir;

        $matches = [];

        $binaries = $package->getBinaries();

        foreach ($binaries as $binary) {
            if (file_exists($sourceDir . DIRECTORY_SEPARATOR . $binary)) {
                $matches[] = $sourceDir . DIRECTORY_SEPARATOR . $binary;
            }

            $globPattern = $sourceDir . DIRECTORY_SEPARATOR . '**' . DIRECTORY_SEPARATOR . $binary;

            $matches = array_merge(
                $matches,
                $this->utils->recursiveGlob($globPattern)
            );
        }

        if (!$matches) {
            $errorMessage = sprintf(
                'Could not locate binaries (%s) from downloaded source',
                implode(
                    ', ',
                    array_unique(
                        array_map(function ($item) {
                            return basename($item);
                        }, $binaries)
                    )
                )
            );
            
            throw new \Exception($errorMessage);
        }

        $executables = array_filter($matches, function ($path) {
            return is_executable($path);
        });

        $this->fileSystem->ensureDirectoryExists($binDir);

        foreach ($executables as $fromPath) {
            $toPath = $binDir . DIRECTORY_SEPARATOR . basename($fromPath);

            $this->fileSystem->copyThenRemove($fromPath, $toPath);

            $platformCode = $this->platformAnalyser->getPlatformCode();

            if ($platformCode !== OsDetector::TYPE_WIN32 && $platformCode !== OsDetector::TYPE_WIN64) {
                \Composer\Util\Silencer::call('chmod', $toPath, 0777 & ~umask());
            }
        }

        return $matches;
    }
}
