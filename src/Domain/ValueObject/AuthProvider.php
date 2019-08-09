<?php
declare(strict_types=1);

namespace App\Domain\ValueObject;

use App\Domain\ValueObject\Token\Action\RemoveAuthProviderToken;

class AuthProvider implements \JsonSerializable
{
    public const PROVIDER_GOOGLE = 'google';
    // todo - others

    private $provider;
    private $removalToken;

    public function __construct(
        string $provider,
        ?RemoveAuthProviderToken $removalToken = null
    ) {
        $this->provider = $provider;
        $this->removalToken = $removalToken;
    }

    public function jsonSerialize()
    {
        return [
            'provider' => $this->provider,
            'removalToken' => $this->removalToken,
            'addUrl' => '/login/' . $this->provider,
        ];
    }
}
