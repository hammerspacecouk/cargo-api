<?php
declare(strict_types=1);

namespace App\Domain\ValueObject;

class OauthState
{
    public const EXPIRY = 'PT1H';
    public const SUBJECT = 'state';

    private string $returnUrl;
    private string $stateId;

    public function __construct(
        string $stateId,
        string $returnUrl
    ) {
        $this->returnUrl = $returnUrl;
        $this->stateId = $stateId;
    }

    /**
     * @param array<string, mixed> $claims
     * @return OauthState
     */
    public static function createFromClaims(array $claims): OauthState
    {
        return new self($claims['id'], $claims['r']);
    }

    /**
     * @return array<string, mixed>
     */
    public function getClaims(): array
    {
        return ['id' => $this->stateId, 'r' => $this->returnUrl];
    }

    public function getReturnUrl(): string
    {
        return $this->returnUrl;
    }

    public function getStateId(): string
    {
        return $this->stateId;
    }
}
