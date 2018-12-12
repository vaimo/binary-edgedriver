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
        $baseUrl = 'https://download.microsoft.com/download';
        
        return [
            self::REQUEST_VERSION => false,
            self::REQUEST_DOWNLOAD => sprintf('%s/{{hash}}/{{file}}', $baseUrl)
        ];
    }
    
    public function getBrowserBinaryPaths()
    {
        return [
            Platform::TYPE_LINUX32 => [],
            Platform::TYPE_LINUX64 => [],
            Platform::TYPE_MAC64 => [],
            Platform::TYPE_WIN32 => [],
            Platform::TYPE_WIN64 => []
        ];
    }
    
    public function getBrowserVersionPollingConfig()
    {
        return [
            'powershell.exe "Get-AppxPackage Microsoft.MicrosoftEdge | %{echo $_.version}"' => ['%s']
        ];
    }
    
    public function getDriverVersionPollingConfig()
    {
        return [
            'wmic datafile where name="%s" get Version /value' => ['Version=%s']
        ];
    }
    
    public function getBrowserDriverVersionMap()
    {
        return [
            '18.00000' => '',
            '17.17134' => '6.17134',
            '16.16299' => '5.16299',
            '15.15063' => '4.15063',
            '14.14393' => '3.14393',
            '13.10586' => '2.10586',
            '12.10240' => '1.10240'
        ];
    }
    public function getDriverVersionHashMap()
    {
        return [
            '6.17134' => 'F/8/A/F8AF50AB-3C3A-4BC4-8773-DC27B32988DD',
            '5.16299' => 'D/4/1/D417998A-58EE-4EFE-A7CC-39EF9E020768',
            '4.15063' => '3/4/2/342316D7-EBE0-4F10-ABA2-AE8E0CDF36DD',
            '3.14393' => '3/2/D/32D3E464-F2EF-490F-841B-05D53C848D15',
            '2.10586' => 'C/0/7/C07EBF21-5305-4EC8-83B1-A6FCC8F93F45',
            '1.10240' => '8/D/0/8D0D08CF-790D-4586-B726-C6469A9ED49C',
        ];
    }
    
    public function getRemoteFileNames()
    {
        return [
            Platform::TYPE_LINUX32 => '',
            Platform::TYPE_LINUX64 => '',
            Platform::TYPE_MAC64 => '',
            Platform::TYPE_WIN32 => 'MicrosoftWebDriver.exe',
            Platform::TYPE_WIN64 => 'MicrosoftWebDriver.exe'
        ];
    }

    public function getExecutableFileNames()
    {
        return [
            Platform::TYPE_LINUX32 => '',
            Platform::TYPE_LINUX64 => '', 
            Platform::TYPE_MAC64 => '',
            Platform::TYPE_WIN32 => 'MicrosoftWebDriver.exe',
            Platform::TYPE_WIN64 => 'MicrosoftWebDriver.exe'
        ];
    }

    public function getExecutableFileRenames()
    {
        return [
            'MicrosoftWebDriver.exe' => 'edgedriver.exe'
        ];
    }
}
