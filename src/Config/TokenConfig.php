<?php declare(strict_types=1);

namespace App\Config;

class TokenConfig
{
    private $privateKey;
    private $issuer;
    private $cookieScope;

    public function __construct(
        string $privateKey,
        string $issuer,
        string $cookieScope
    ) {
        $this->privateKey = $privateKey;
        $this->issuer = $issuer;
        $this->cookieScope = $cookieScope;
    }

    public function getPrivateKey(): string
    {
        return $this->privateKey;
    }

    public function getIssuer(): string
    {
        return $this->issuer;
    }

    public function getCookieScope(): string
    {
        return $this->cookieScope;
    }
}
