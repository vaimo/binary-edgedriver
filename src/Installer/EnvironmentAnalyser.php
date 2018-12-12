<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\EdgeDriver\Installer;

class EnvironmentAnalyser
{
    /**
     * @var \Vaimo\EdgeDriver\Plugin\Config
     */
    private $pluginConfig;
    
    /**
     * @var \Vaimo\EdgeDriver\Installer\PlatformAnalyser
     */
    private $platformAnalyser;
    
    /**
     * @var \Vaimo\EdgeDriver\Installer\VersionResolver
     */
    private $versionResolver;

    /**
     * @param \Vaimo\EdgeDriver\Plugin\Config $pluginConfig
     */
    public function __construct(
        \Vaimo\EdgeDriver\Plugin\Config $pluginConfig
    ) {
        $this->pluginConfig = $pluginConfig;
        
        $this->platformAnalyser = new \Vaimo\EdgeDriver\Installer\PlatformAnalyser();
        $this->versionResolver = new \Vaimo\EdgeDriver\Installer\VersionResolver();
    }

    public function resolveBrowserVersion()
    {
        $platformCode = $this->platformAnalyser->getPlatformCode();
        $binaryPaths = $this->pluginConfig->getBrowserBinaryPaths();

        if (!isset($binaryPaths[$platformCode])) {
            return '';
        }

        return $this->versionResolver->pollForVersion(
            $binaryPaths[$platformCode],
            $this->pluginConfig->getBrowserVersionPollingConfig()
        );
    }
}
