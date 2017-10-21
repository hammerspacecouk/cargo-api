<?php
declare(strict_types=1);

namespace App\Infrastructure;

class ApplicationConfig
{
    private $hostnameApi;
    private $hostnameWeb;
    private $cookieScope;
    private $distanceMultiplier;
    private $emailFromName;
    private $emailFromAddress;
    private $tokenPrivateKey;
    private $tokenIssuer;

    public function __construct(
        string $hostnameApi,
        string $hostnameWeb,
        string $cookieScope,
        float $distanceMultiplier,
        string $emailFromName,
        string $emailFromAddress,
        string $tokenPrivateKey,
        string $tokenIssuer
    ) {
        $this->hostnameApi = $hostnameApi;
        $this->hostnameWeb = $hostnameWeb;
        $this->cookieScope = $cookieScope;
        $this->distanceMultiplier = $distanceMultiplier;
        $this->emailFromName = $emailFromName;
        $this->emailFromAddress = $emailFromAddress;
        $this->tokenPrivateKey = $tokenPrivateKey;
        $this->tokenIssuer = $tokenIssuer;
    }

    public function getApiHostname(): string
    {
        return $this->hostnameApi;
    }

    public function getWebHostname(): string
    {
        return $this->hostnameWeb;
    }

    public function getCookieScope(): string
    {
        return $this->cookieScope;
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

    public function getTokenPrivateKey(): string
    {
        return $this->tokenPrivateKey;
    }

    public function getTokenIssuer(): string
    {
        return $this->tokenIssuer;
    }
}
