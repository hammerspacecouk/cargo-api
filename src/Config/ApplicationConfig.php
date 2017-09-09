<?php
declare(strict_types=1);

namespace App\Config;

class ApplicationConfig
{
    private $hostname;
    private $distanceMultiplier;
    private $emailFromName;
    private $emailFromAddress;

    public function __construct(
        string $hostname,
        float $distanceMultiplier,
        string $emailFromName,
        string $emailFromAddress
    ) {
        $this->hostname = $hostname;
        $this->distanceMultiplier = $distanceMultiplier;
        $this->emailFromName = $emailFromName;
        $this->emailFromAddress = $emailFromAddress;
    }

    public function getHostname(): string
    {
        return $this->hostname;
    }

    public function getDistanceMultiplier(): float
    {
        return $this->distanceMultiplier;
    }

    public function getEmailFromName(): string
    {
        return $this->emailFromName;
    }

    public function getEmailFromAddress(): string
    {
        return $this->emailFromAddress;
    }
}
