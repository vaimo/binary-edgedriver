<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\EdgeDriver;

class Installer
{
    /**
     * @var \Composer\Composer
     */
    private $composerRuntime;

    /**
     * @var \Composer\IO\IOInterface
     */
    private $io;
    
    /**
     * @var \Vaimo\EdgeDriver\Installer\PackageManager 
     */
    private $packageManager;

    /**
     * @var \Vaimo\EdgeDriver\Installer\Utils 
     */
    private $utils;
    
    /**
     * @param \Composer\Composer $composerRuntime
     * @param \Composer\IO\IOInterface $io
     */
    public function __construct(
        \Composer\Composer $composerRuntime,
        \Composer\IO\IOInterface $io
    ) {
        $this->composerRuntime = $composerRuntime;
        $this->io = $io;

        $this->packageManager = new \Vaimo\EdgeDriver\Installer\PackageManager();
        $this->utils = new \Vaimo\EdgeDriver\Installer\Utils();
    }
    
    public function execute()
    {
        $binaryDir = $this->composerRuntime->getConfig()->get('bin-dir');

        $pluginConfig = new \Vaimo\EdgeDriver\Plugin\Config($this->composerRuntime->getPackage());
        $projectAnalyser = new \Vaimo\EdgeDriver\Installer\ProjectAnalyser($pluginConfig);
        
        $version = $projectAnalyser->resolveRequiredDriverVersion();

        if ($this->io->isVerbose()) {
            $this->io->write(sprintf('<comment>Using version %s</comment>', $version));
        }

        $currentVersion = $projectAnalyser->resolveInstalledDriverVersion($binaryDir);

        if (strpos($currentVersion, $version) === 0) {
            if ($this->io->isVerbose()) {
                $this->io->write(sprintf('Required version (v%s) already installed', $version));
            }

            return;
        }
        
        $this->io->write(sprintf(
            '<info>Installing <comment>%s</comment> (v%s)</info>',
            $pluginConfig->getDriverName(),
            $version
        ));

        $localRepository = $this->composerRuntime->getRepositoryManager()
            ->getLocalRepository();
        
        $pluginPackage = $projectAnalyser->resolvePackageForNamespace(
            $localRepository->getCanonicalPackages(),
            __NAMESPACE__
        );
        
        $downloadManager = new \Vaimo\EdgeDriver\Installer\DownloadManager(
            $this->composerRuntime->getDownloadManager(),
            $pluginPackage,
            $this->createCacheManager($pluginPackage->getName()),
            $pluginConfig
        );
        
        try {
            $package = $downloadManager->downloadRelease([$version]);
        } catch (\Exception $exception) {
            $this->io->error($exception->getMessage());
            return;
        }
  
        try {
            $this->packageManager->installBinaries($package, $binaryDir);

            $this->io->write('');
            $this->io->write('<info>Done</info>');
        } catch (\Exception $exception) {
            $this->io->error($exception->getMessage());
        }
    }

    private function createCacheManager($cacheName)
    {
        $cacheDir = $this->composerRuntime->getConfig()->get('cache-dir');
        
        return new \Composer\Cache(
            $this->io,
            $this->utils->composePath($cacheDir, 'files', $cacheName, 'downloads')
        );
    }
}
