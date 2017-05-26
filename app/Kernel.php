<?php
declare(strict_types=1);
namespace App;

use Symfony\Component\HttpKernel\Kernel as SymfonyKernel;
use Symfony\Component\Config\Loader\LoaderInterface;

class Kernel extends SymfonyKernel
{
    public function registerBundles()
    {
        $bundles = [
            new \Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new \Symfony\Bundle\MonologBundle\MonologBundle(),
        ];

        if (in_array($this->getEnvironment(), ['dev'], true)) {
            $bundles[] = new \Symfony\Bundle\DebugBundle\DebugBundle();
        }

        return $bundles;
    }

    public function getRootDir()
    {
        return __DIR__;
    }

    public function getCacheDir()
    {
        return dirname(__DIR__).'/tmp/cache/'.$this->getEnvironment();
    }

    public function getLogDir()
    {
        return dirname(__DIR__).'/tmp/logs';
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $dir = '';
        $env = $this->getEnvironment();
        if ($env) {
            $dir = $env . '/';
        }

        $loader->load($this->getRootDir() . '/config/' . $dir . 'config.yml');
    }
}
