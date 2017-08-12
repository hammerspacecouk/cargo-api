<?php
declare(strict_types = 1);
namespace App\Config;

class TokenConfig
{
    private $privateKey;

    private $cookieName;

    private $issuer;

    private $audience;

    public function __construct(
        string $privateKey,
        string $cookieName,
        string $issuer,
        string $audience
    ) {
        $this->privateKey = $privateKey;
        $this->cookieName = $cookieName;
        $this->issuer = $issuer;
        $this->audience = $audience;
    }

    public function getPrivateKey(): string
    {
        return $this->privateKey;
    }

    public function getCookieName(): string
    {
        return $this->cookieName;
    }

    public function getIssuer(): string
    {
        return $this->issuer;
    }

    public function getAudience(): string
    {
        return $this->audience;
    }
}
