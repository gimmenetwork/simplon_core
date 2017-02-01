<?php

namespace Simplon\Core;

use Simplon\Core\Interfaces\CoreContextInterface;
use Simplon\Core\Storage\CookieStorage;
use Simplon\Core\Storage\SessionStorage;
use Simplon\Core\Utils\Config;
use Simplon\Core\Utils\EventsHandler;
use Simplon\Locale\Readers\PhpFileReader;

/**
 * Class CoreContext
 * @package Simplon\Core
 */
abstract class CoreContext implements CoreContextInterface
{
    const APP_PATH = __DIR__ . '/../../../../src';
    const APP_ENV_DEV = 'dev';
    const APP_ENV_STAGING = 'staging';
    const APP_ENV_PRODUCTION = 'production';

    /**
     * @var Config
     */
    private $config;
    /**
     * @var Config[]
     */
    private $configCache;
    /**
     * @var SessionStorage
     */
    private $sessionStorage;
    /**
     * @var CookieStorage
     */
    private $cookieStorage;
    /**
     * @var EventsHandler
     */
    private $eventsHandler;

    /**
     * @param array $paths
     *
     * @return PhpFileReader
     */
    public function getLocaleFileReader(array $paths): PhpFileReader
    {
        return new PhpFileReader($paths);
    }

    /**
     * @param string $workingDir
     *
     * @return Config
     */
    public function getConfig(string $workingDir = null): Config
    {
        if (!$this->config)
        {
            /** @noinspection PhpIncludeInspection */
            $this->config = new Config(require self::APP_PATH . '/Configs/config.php');

            foreach ([self::APP_ENV_STAGING, self::APP_ENV_PRODUCTION] as $env)
            {
                if (getenv('APP_ENV') === $env)
                {
                    $filePath = self::APP_PATH . '/Configs/' . $env . '.php';

                    if (file_exists($filePath))
                    {
                        /** @noinspection PhpIncludeInspection */
                        $this->config->addConfig(require $filePath);
                    }

                    break;
                }
            }
        }

        if ($workingDir)
        {
            $md5WorkingDir = md5($workingDir);

            if (empty($this->configCache[$md5WorkingDir]))
            {
                if (file_exists($workingDir . '/Configs/config.php'))
                {
                    /** @noinspection PhpIncludeInspection */
                    $this->config->addConfig(require $workingDir . '/Configs/config.php');

                    foreach ([self::APP_ENV_STAGING, self::APP_ENV_PRODUCTION] as $env)
                    {
                        if (getenv('APP_ENV') === $env)
                        {
                            $filePath = $workingDir . '/Configs/' . $env . '.php';

                            if (file_exists($filePath))
                            {
                                /** @noinspection PhpIncludeInspection */
                                $this->config->addConfig(require $filePath);
                            }

                            break;
                        }
                    }
                }

                $this->configCache[$md5WorkingDir] = $this->config;
            }

            return $this->configCache[$md5WorkingDir];
        }

        return $this->config;
    }

    /**
     * @return EventsHandler
     */
    public function getEventsHandler(): EventsHandler
    {
        if (!$this->eventsHandler)
        {
            $this->eventsHandler = new EventsHandler();
        }

        return $this->eventsHandler;
    }

    /**
     * @return SessionStorage
     */
    public function getSessionStorage(): SessionStorage
    {
        if (!$this->sessionStorage)
        {
            $this->sessionStorage = new SessionStorage();
        }

        return $this->sessionStorage;
    }

    /**
     * @param string $namespace
     *
     * @return CookieStorage
     */
    public function getCookieStorage(string $namespace = 'CORE'): CookieStorage
    {
        if (!$this->cookieStorage)
        {
            $this->cookieStorage = new CookieStorage($namespace);
        }

        return $this->cookieStorage;
    }
}