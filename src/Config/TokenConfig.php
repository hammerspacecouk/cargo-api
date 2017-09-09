<?php
declare(strict_types=1);

namespace App\Config;

class TokenConfig
{
    private $privateKey;

    private $issuer;

    public function __construct(
        string $privateKey,
        string $issuer
    ) {
        $this->privateKey = $privateKey;
        $this->issuer = $issuer;
    }

    public function getPrivateKey(): string
    {
        return $this->privateKey;
    }

    public function getIssuer(): string
    {
        return $this->issuer;
    }
}
