<?php
declare(strict_types=1);
namespace App;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\Bundle\DoctrineCacheBundle\DoctrineCacheBundle;
use Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle;
use Symfony\Bundle\DebugBundle\DebugBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Bundle\WebProfilerBundle\WebProfilerBundle;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpKernel\Kernel as SymfonyKernel;
use Symfony\Component\Config\Loader\LoaderInterface;

class Kernel extends SymfonyKernel
{
    public const ENV_DEV = 'dev';
    public const ENV_ALPHA = 'alpha';
    public const ENV_BETA = 'beta';
    public const ENV_PROD = '';

    public function __construct(string $environment, bool $debug)
    {
        parent::__construct($environment, $debug);

        date_default_timezone_set('UTC'); // servers should always be UTC

        if ($environment === self::ENV_DEV) {
            $dotenv = new Dotenv();
            $dotenv->load(__DIR__.'/../.env');
        }
    }

    public function registerBundles(): array
    {
        $bundles = [
            new FrameworkBundle(),
            new MonologBundle(),
            new DoctrineBundle(),
            new DoctrineCacheBundle(),
            new DoctrineMigrationsBundle(),
        ];

        if (in_array($this->getEnvironment(), [self::ENV_DEV], true)) {
            $bundles[] = new DebugBundle();
            $bundles[] = new WebProfilerBundle();
            $bundles[] = new TwigBundle();
        }

        return $bundles;
    }

    public function getRootDir(): string
    {
        return __DIR__;
    }

    public function getCacheDir(): string
    {
        return dirname(__DIR__).'/tmp/cache/'.$this->getEnvironment();
    }

    public function getLogDir(): string
    {
        return dirname(__DIR__).'/tmp/logs';
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $dir = '';
        $env = $this->getEnvironment();
        if ($env) {
            $dir = $env . '/';
        }

        $loader->load($this->getRootDir() . '/config/' . $dir . 'config.yml');
    }
}
