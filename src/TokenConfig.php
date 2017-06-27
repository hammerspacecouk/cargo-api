<?php
declare(strict_types = 1);
namespace App;

class TokenConfig
{
    private $privateKey;

    private $cookieName;

    private $issuer;

    private $audience;

    private $id;

    public function __construct(
        string $privateKey,
        string $cookieName,
        string $issuer,
        string $audience,
        string $id
    ) {
        $this->privateKey = $privateKey;
        $this->cookieName = $cookieName;
        $this->issuer = $issuer;
        $this->audience = $audience;
        $this->id = $id;
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

    public function getId(): string
    {
        return $this->id;
    }
}
