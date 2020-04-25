<?php
declare(strict_types=1);

namespace App\Domain\ValueObject;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use function App\Functions\Strings\filteredImplode;

class TokenId
{
    private const SEPARATOR = '/';

    private UuidInterface $deterministicItemId;
    private ?UuidInterface $groupId;

    public function __construct(
        UuidInterface $deterministicItemId,
        ?UuidInterface $groupId = null
    ) {
        $this->deterministicItemId = $deterministicItemId;
        $this->groupId = $groupId;
    }

    /**
     * @param string $tokenIdString
     * @return array
     */
    public static function toIds(string $tokenIdString): array
    {
        return \array_map(static function (string $idString) {
            return Uuid::fromString($idString);
        }, \explode(self::SEPARATOR, $tokenIdString));
    }

    public static function fromString(string $idsString): TokenId
    {
        return new self(...\array_map(static function (string $id) {
            return Uuid::fromString($id);
        }, \explode('/', $idsString)));
    }

    public function __toString(): string
    {
        return filteredImplode(self::SEPARATOR, [$this->deterministicItemId, $this->groupId]);
    }
}
