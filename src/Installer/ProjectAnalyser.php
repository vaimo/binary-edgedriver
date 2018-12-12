<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\EdgeDriver\Installer;

use Vaimo\EdgeDriver\Plugin\Config;

class ProjectAnalyser
{
    /**
     * @var \Composer\Package\Version\VersionParser
     */
    private $versionParser;
    
    /**
     * @var \Vaimo\EdgeDriver\Plugin\Config
     */
    private $pluginConfig;

    /**
     * @var \Vaimo\EdgeDriver\Installer\EnvironmentAnalyser
     */
    private $environmentAnalyser;

    /**
     * @var \Vaimo\EdgeDriver\Installer\PlatformAnalyser
     */
    private $platformAnalyser;

    /**
     * @var \Vaimo\EdgeDriver\Installer\VersionResolver
     */
    private $versionResolver;

    /**
     * @var \Composer\Package\CompletePackage
     */
    private $ownerPackage;
    
    /**
     * @var \Vaimo\EdgeDriver\Installer\Utils
     */
    private $utils;
    
    /**
     * @param \Vaimo\EdgeDriver\Plugin\Config $pluginConfig
     */
    public function __construct(
        \Vaimo\EdgeDriver\Plugin\Config $pluginConfig
    ) {
        $this->pluginConfig = $pluginConfig;

        $this->environmentAnalyser = new \Vaimo\EdgeDriver\Installer\EnvironmentAnalyser($pluginConfig);

        $this->versionParser = new \Composer\Package\Version\VersionParser();

        $this->platformAnalyser = new \Vaimo\EdgeDriver\Installer\PlatformAnalyser();
        $this->versionResolver = new \Vaimo\EdgeDriver\Installer\VersionResolver();
        $this->utils = new \Vaimo\EdgeDriver\Installer\Utils();
    }
    
    public function resolvePlatformSupport()
    {
        $platformCode = $this->platformAnalyser->getPlatformCode();
        
        $fileNames = $this->pluginConfig->getExecutableFileNames();

        return (bool)($fileNames[$platformCode] ?? false);
    }
    
    public function resolveInstalledDriverVersion($binaryDir)
    {
        $platformCode = $this->platformAnalyser->getPlatformCode();

        $executableNames = $this->pluginConfig->getExecutableFileNames();
        $remoteFiles = $this->pluginConfig->getRemoteFileNames();

        if (!isset($executableNames[$platformCode], $remoteFiles[$platformCode])) {
            throw new \Exception('Failed to resolve a file for the platform. Download driver manually');
        }

        $executableName = $executableNames[$platformCode];

        $driverPath = realpath($this->utils->composePath($binaryDir, $executableName));
        
        return $this->versionResolver->pollForVersion(
            [$driverPath],
            $this->pluginConfig->getDriverVersionPollingConfig()
        );
    }

    public function resolveRequiredDriverVersion()
    {
        $preferences = $this->pluginConfig->getPreferences();
        $requestConfig = $this->pluginConfig->getRequestUrlConfig();

        $version = $preferences['version'];
        
        if (!$preferences['version']) {
            $version = $this->resolveBrowserDriverVersion(
                $this->environmentAnalyser->resolveBrowserVersion()
            );

            $versionCheckUrl = $requestConfig[Config::REQUEST_VERSION];

            if (!$version && $versionCheckUrl) {
                $version = trim(@file_get_contents($versionCheckUrl));
            }

            if (!$version) {
                $versionMap = array_filter($this->pluginConfig->getBrowserDriverVersionMap());
                $version = reset($versionMap);   
            }
        }

        try {
            $this->versionParser->parseConstraints($version);
        } catch (\UnexpectedValueException $exception) {
            throw new \Exception(sprintf('Incorrect version string: "%s"', $version));
        }
        
        return $version;
    }

    private function resolveBrowserDriverVersion($browserVersion)
    {
        $chromeVersion = $browserVersion;

        if (!$chromeVersion) {
            return '';
        }

        $majorVersion = strtok($chromeVersion, '.');

        $driverVersionMap = $this->pluginConfig->getBrowserDriverVersionMap();

        foreach ($driverVersionMap as $browserMajor => $driverVersion) {
            if ($majorVersion < $browserMajor) {
                continue;
            }

            return $driverVersion;
        }

        return '';
    }

    public function resolvePackageForNamespace(array $packages, $namespace)
    {
        if ($this->ownerPackage === null) {
            foreach ($packages as $package) {
                if ($package->getType() !== 'composer-plugin') {
                    continue;
                }

                $autoload = $package->getAutoload();

                if (!isset($autoload['psr-4'])) {
                    continue;
                }

                $matches = array_filter(
                    array_keys($autoload['psr-4']),
                    function ($item) use ($namespace) {
                        return strpos($namespace, rtrim($item, '\\')) === 0;
                    }
                );

                if (!$matches) {
                    continue;
                }

                $this->ownerPackage = $package;

                break;
            }
        }

        if (!$this->ownerPackage) {
            throw new \Exception('Failed to detect the plugin package');
        }

        return $this->ownerPackage;
    }
}
