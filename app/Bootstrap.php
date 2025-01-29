<?php

declare(strict_types=1);

namespace App;

use Dotenv;
use Nette;
use Nette\Bootstrap\Configurator;

class Bootstrap
{
    private Configurator $configurator;
    private string $rootDir;


    public function __construct()
    {
        $this->rootDir = dirname(__DIR__);
        $this->configurator = new Configurator;
        $this->configurator->setTempDirectory($this->rootDir . '/temp');
    }


    public function bootWebApplication(): Nette\DI\Container
    {
        $this->initializeEnvironment();
        $this->loadEnvironmentVariables();
        $this->setupContainer();
        return $this->configurator->createContainer();
    }


    public function initializeEnvironment(): void
    {
        $this->configurator->setDebugMode('127.0.0.1');
        $this->configurator->enableTracy($this->rootDir . '/log');

        $this->configurator->createRobotLoader()
            ->addDirectory(__DIR__)
            ->register();
    }


    private function setupContainer(): void
    {
        $configDir = $this->rootDir . '/config';
        $this->configurator->addConfig($configDir . '/common.neon');
        if ($this->configurator->isDebugMode()) {
            $this->configurator->addConfig($configDir . '/common.local.neon');
        }
        $this->configurator->addConfig($configDir . '/services.neon');
    }

    private function loadEnvironmentVariables(): void
    {
        if ($this->configurator->isDebugMode()) {
            try {
                $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
                $dotenv->safeLoad();
                $dotenv->required(['CATALOG_PRODUCT_SHOPTET_XML_URL']);
            } catch (Dotenv\Exception\InvalidEncodingException|Dotenv\Exception\InvalidFileException $e) {
            }

            $this->configurator->addDynamicParameters([
                'env' => $_ENV,
            ]);
        }
    }
}
