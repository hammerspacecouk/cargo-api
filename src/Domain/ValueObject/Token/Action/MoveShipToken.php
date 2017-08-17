<?php
declare(strict_types = 1);
namespace App\Domain\ValueObject\Token\Action;

use App\Domain\Exception\InvalidTokenException;
use Lcobucci\JWT\Token;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class MoveShipToken extends AbstractActionToken
{
    protected const TYPE = 'move-ship';

    private const KEY_SHIP = 'shp';
    private const KEY_CHANNEL = 'cnl';
    private const KEY_REVERSED = 'rvsd';

    private $cookies = [];

    public function __construct(Token $token, array $cookies = [])
    {
        parent::__construct($token);
        $this->cookies = $cookies;
    }

    public function getShipId(): UuidInterface
    {
        if ($this->token->hasClaim(self::KEY_SHIP)) {
            return Uuid::fromString($this->token->getClaim(self::KEY_SHIP));
        }
        throw new InvalidTokenException('No Ship ID found');
    }

    public function getCookies(): array
    {
        return $this->cookies;
    }

    public static function makeClaims(
        UuidInterface $shipId,
        UuidInterface $channelId,
        bool $isReversed
    ): array {
        return parent::createClaims([
            self::KEY_SHIP => (string) $shipId,
            self::KEY_CHANNEL => (string) $channelId,
            self::KEY_REVERSED => $isReversed,
        ]);
    }
}
