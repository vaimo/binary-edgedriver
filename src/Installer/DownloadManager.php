<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\EdgeDriver\Installer;

use Vaimo\EdgeDriver\Plugin\Config;

class DownloadManager
{
    /**
     * @var \Composer\Downloader\DownloadManager 
     */
    private $downloadManager;

    /**
     * @var \Composer\Package\CompletePackage
     */
    private $ownerPackage;
    
    /**
     * @var \Composer\Cache
     */
    private $cacheManager;

    /**
     * @var \Vaimo\EdgeDriver\Plugin\Config
     */
    private $pluginConfig;

    /**
     * @var \Composer\Package\Version\VersionParser 
     */
    private $versionParser;
    
    /**
     * @var \Vaimo\EdgeDriver\Installer\PlatformAnalyser
     */
    private $platformAnalyser;

    /**
     * @var \Vaimo\EdgeDriver\Installer\Utils
     */
    private $utils;

    /**
     * @param \Composer\Downloader\DownloadManager $downloadManager
     * @param \Composer\Package\CompletePackage $ownerPackage
     * @param \Composer\Cache $cacheManager
     * @param \Vaimo\EdgeDriver\Plugin\Config $pluginConfig
     */
    public function __construct(
        \Composer\Downloader\DownloadManager $downloadManager,
        \Composer\Package\CompletePackage $ownerPackage,
        \Composer\Cache $cacheManager,
        \Vaimo\EdgeDriver\Plugin\Config $pluginConfig
    ) {
        $this->downloadManager = $downloadManager;
        $this->ownerPackage = $ownerPackage;
        $this->cacheManager = $cacheManager;

        $this->pluginConfig = $pluginConfig;

        $this->versionParser = new \Composer\Package\Version\VersionParser();
        $this->platformAnalyser = new \Vaimo\EdgeDriver\Installer\PlatformAnalyser();
        $this->utils = new \Vaimo\EdgeDriver\Installer\Utils();
    }
    
    public function downloadRelease(array $versions)
    {
        $targetDir = $this->utils->composePath(
            rtrim($this->cacheManager->getRoot(), DIRECTORY_SEPARATOR),
            reset($versions)
        );
        
        while ($version = array_shift($versions)) {
            $package = $this->createComposerVirtualPackage($version, $targetDir);

            try {
                $downloader = $this->downloadManager->getDownloaderForInstalledPackage($package);

                $downloader->download($package, $targetDir, false);
                
                return $package;
            } catch (\Composer\Downloader\TransportException $exception) {
                if ($exception->getStatusCode() === 404 && $versions) {
                    continue;
                }

                $errorMessage = sprintf(
                    'Transport failure %s while downloading v%s: %s',
                    $exception->getStatusCode(),
                    $version,
                    $exception->getMessage()
                );
                
                throw new \Exception($errorMessage);
            } catch (\Exception $exception) {
                $errorMessage = sprintf(
                    'Unexpected error while downloading v%s: %s',
                    $version,
                    $exception->getMessage()
                );

                throw new \Exception($errorMessage);
            }
        }

        throw new \Exception('Failed to download requested driver');
    }
    
    private function createComposerVirtualPackage($version, $targetDir)
    {
        $remoteFile = $this->getDownloadUrl($version);
        
        $platformCode = $this->platformAnalyser->getPlatformCode();

        $package = new \Composer\Package\Package(
            sprintf('%s-virtual-package', $this->ownerPackage->getName()),
            $this->versionParser->normalize($version),
            $version
        );
        
        $executableNames = $this->pluginConfig->getExecutableFileNames();

        $executableName = $executableNames[$platformCode];

        $package->setBinaries([$executableName]);
        $package->setInstallationSource('dist');
        $package->setDistType(pathinfo($remoteFile, PATHINFO_EXTENSION) === 'zip' ? 'zip' : 'tar');
        $package->setTargetDir($targetDir);
        $package->setDistUrl($remoteFile);

        return $package;
    }

    private function getDownloadUrl($version)
    {
        $requestConfig = $this->pluginConfig->getRequestUrlConfig();

        $platformCode = $this->platformAnalyser->getPlatformCode();

        $remoteFiles = $this->pluginConfig->getRemoteFileNames();

        if (!isset($remoteFiles[$platformCode])) {
            throw new \Exception('Failed to resolve a file for the platform. Download driver manually');
        }

        $remoteFileName = $this->utils->stringFromTemplate(
            $remoteFiles[$platformCode],
            ['version' => $version]
        );
        
        return $this->utils->stringFromTemplate(
            $requestConfig[Config::REQUEST_DOWNLOAD],
            ['version' => $version, 'file' => $remoteFileName]
        );
    }
}
