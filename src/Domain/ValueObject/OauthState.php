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

    /**
     * @param array<string, mixed> $claims
     * @return OauthState
     */
    public static function createFromClaims(array $claims): OauthState
    {
        return new self($claims['r']);
    }

    /**
     * @return array<string, mixed>
     */
    public function getClaims(): array
    {
        return ['r' => $this->returnUrl];
    }

    public function getReturnUrl(): string
    {
        return $this->returnUrl;
    }
}
