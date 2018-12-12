<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\EdgeDriver\Plugin;

use Vaimo\EdgeDriver\Installer\PlatformAnalyser as Platform;

class Config
{
    const REQUEST_VERSION = 'version';
    const REQUEST_DOWNLOAD = 'download';
    
    /**
     * @var \Composer\Package\PackageInterface
     */
    private $configOwner;

    /**
     * @param \Composer\Package\PackageInterface $configOwner
     */
    public function __construct(
        \Composer\Package\PackageInterface $configOwner
    ) {
        $this->configOwner = $configOwner;
    }

    public function getPreferences()
    {
        $extra = $this->configOwner->getExtra();

        $defaults = [
            'version' => null
        ];

        return array_replace(
            $defaults,
            isset($extra['edgedriver']) ? $extra['edgedriver'] : []
        );
    }

    public function getDriverName()
    {
        return 'EdgeDriver';
    }
    
    public function getRequestUrlConfig()
    {
        $baseUrl = 'https://edgedriver.storage.googleapis.com';
        
        return [
            self::REQUEST_VERSION => sprintf('%s/LATEST_RELEASE', $baseUrl),
            self::REQUEST_DOWNLOAD => sprintf('%s/{{version}}/{{file}}', $baseUrl)
        ];
    }
    
    public function getBrowserBinaryPaths()
    {
        return [
            Platform::TYPE_LINUX32 => [
                '/usr/bin/google-chrome'
            ],
            Platform::TYPE_LINUX64 => [
                '/usr/bin/google-chrome'
            ],
            Platform::TYPE_MAC64 => [
                '/Applications/Google\ Chrome.app/Contents/MacOS/Google\ Chrome'
            ]
        ];
    }
    
    public function getBrowserVersionPollingConfig()
    {
        return [
            '%s -version' => ['Google Chrome %s']  
        ];
    }
    
    public function getDriverVersionPollingConfig()
    {
        return [
            '%s --version' => ['EdgeDriver %s (']
        ];
    }
    
    public function getBrowserDriverVersionMap()
    {
        return [
            '72' => '',
            '69' => '2.44',
            '68' => '2.42',
            '67' => '2.41',
            '66' => '2.40',
            '65' => '2.38',
            '64' => '2.37',
            '63' => '2.36',
            '62' => '2.35',
            '61' => '2.34',
            '60' => '2.33',
            '57' => '2.28',
            '54' => '2.25',
            '53' => '2.24',
            '51' => '2.22',
            '44' => '2.19',
            '42' => '2.15',
            '1' => '2.0'
        ];
    }
    
    public function getRemoteFileNames()
    {
        return [
            Platform::TYPE_LINUX32 => 'edgedriver_linux32.zip',
            Platform::TYPE_LINUX64 => 'edgedriver_linux64.zip',
            Platform::TYPE_MAC64 => 'edgedriver_mac64.zip',
            Platform::TYPE_WIN32 => 'edgedriver_win32.zip',
            Platform::TYPE_WIN64 => 'edgedriver_win32.zip'
        ];
    }

    public function getExecutableFileNames()
    {
        return [
            Platform::TYPE_LINUX32 => 'edgedriver',
            Platform::TYPE_LINUX64 => 'edgedriver',
            Platform::TYPE_MAC64 => 'edgedriver',
            Platform::TYPE_WIN32 => 'edgedriver.exe',
            Platform::TYPE_WIN64 => 'edgedriver.exe'
        ];
    }
}
