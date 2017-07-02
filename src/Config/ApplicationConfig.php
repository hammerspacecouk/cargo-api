<?php
declare(strict_types = 1);
namespace App\Config;

class ApplicationConfig
{
    private $hostname;

    public function __construct(
        string $hostname
    ) {
        $this->hostname = $hostname;
    }

    public function getHostname(): string
    {
        return $this->hostname;
    }
}
