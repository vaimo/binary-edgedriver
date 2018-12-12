<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\EdgeDriver;

class Plugin implements \Composer\Plugin\PluginInterface, \Composer\EventDispatcher\EventSubscriberInterface
{
    public function activate(\Composer\Composer $composer, \Composer\IO\IOInterface $io)
    {
    }
    
    public static function getSubscribedEvents()
    {
        return [
            \Composer\Script\ScriptEvents::POST_INSTALL_CMD => 'installDriver',
            \Composer\Script\ScriptEvents::POST_UPDATE_CMD => 'installDriver',
        ];
    }
    
    public function installDriver(\Composer\Script\Event $event)
    {
        $composerRuntime = $event->getComposer();
        $io = $event->getIo();

        $driverInstaller = new \Vaimo\WebDriverBinaryDownloader\Installer($composerRuntime, $io);
        
        $pluginConfig = new \Vaimo\EdgeDriver\Plugin\Config($composerRuntime->getPackage());

        $driverInstaller->executeWithConfig($pluginConfig);
    }
}
