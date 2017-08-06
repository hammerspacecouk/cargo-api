<?php
declare(strict_types = 1);
namespace App\Config;

class ApplicationConfig
{
    private $hostname;
    private $distanceMultiplier;

    public function __construct(
        string $hostname,
        int $distanceMultiplier
    ) {
        $this->hostname = $hostname;
        $this->distanceMultiplier = $distanceMultiplier;
    }

    public function getHostname(): string
    {
        return $this->hostname;
    }

    public function getDistanceMultiplier(): float
    {
        return $this->distanceMultiplier;
    }
}
