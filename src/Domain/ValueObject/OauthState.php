<?php
declare(strict_types=1);

namespace App\Domain\ValueObject;

class OauthState
{
    public const EXPIRY = 'PT1H';
    public const SUBJECT = 'state';

    private $returnUrl;

    public function __construct(
        string $returnUrl
    ) {
        $this->returnUrl = $returnUrl;
    }

    public static function createFromClaims(array $claims)
    {
        return new self($claims['r']);
    }

    public function getClaims()
    {
        return ['r' => $this->returnUrl];
    }

    public function getReturnUrl(): string
    {
        return $this->returnUrl;
    }
}
