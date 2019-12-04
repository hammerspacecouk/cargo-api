<?php
declare(strict_types=1);

namespace App\Domain\ValueObject\Token;

use App\Domain\Exception\InvalidTokenException;
use App\Domain\ValueObject\TokenId;
use DateTimeImmutable;
use ParagonIE\Paseto\JsonToken;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use function App\Functions\Strings\shortHash;

abstract class AbstractToken
{
    public const EXPIRY = 'PT1H';
    public const TOKEN_HEADER = 'v2.local.';

    /**
     * @var JsonToken
     */
    protected $token;
    /**
     * @var string
     */
    protected $tokenString;
    private $id;
    private $expiry;

    public static function getSubject(): string
    {
        return shortHash(static::class, 8);
    }

    public function __construct(JsonToken $token, string $tokenString)
    {
        $this->validateTokenType($token);
        $this->token = $token;
        $this->tokenString = $tokenString;
        $this->id = TokenId::fromString($token->getJti());
        $this->expiry = DateTimeImmutable::createFromMutable($token->getExpiration());
    }

    public function getOriginalToken(): JsonToken
    {
        return $this->token;
    }

    public function getId(): TokenId
    {
        return $this->id;
    }

    public function getExpiry(): DateTimeImmutable
    {
        return $this->expiry;
    }

    public function __toString(): string
    {
        return \str_replace(self::TOKEN_HEADER, '', $this->tokenString);
    }

    protected static function create(array $claims, TokenId $id = null): array
    {
        $tokenArgs = [
            $claims,
            static::getSubject(),
            static::EXPIRY,
        ];
        if ($id) {
            $tokenArgs[] = $id;
        }
        return $tokenArgs;
    }

    protected function getAnOptionalId(string $key): ?UuidInterface
    {
        $id = $this->token->get($key);
        if (!empty($id)) {
            return Uuid::fromString($id);
        }
        return null;
    }

    private function validateTokenType(JsonToken $token): void
    {
        if ($token->getSubject() !== static::getSubject()) {
            throw new InvalidTokenException('Token did not match expected type');
        }
    }
}
