<?php
declare(strict_types=1);

namespace App\Domain\ValueObject\Token\Action;

use App\Domain\Exception\DataNotFetchedException;
use App\Domain\ValueObject\Token\AbstractToken;
use JsonSerializable;
use ParagonIE\Paseto\JsonToken;

abstract class AbstractActionToken extends AbstractToken implements JsonSerializable
{
    private $path;

    public function __construct(JsonToken $token, string $tokenString, ?string $path = null)
    {
        parent::__construct($token, $tokenString);
        $this->path = $path;
    }

    /**
     * @return array<string, string>
     */
    public function jsonSerialize(): array
    {
        if (!$this->path) {
            throw new DataNotFetchedException('Tried to render a token without setting a path');
        }

        return [
            'path' => '/token',
            'token' => $this->path . $this,
        ];
    }
}
