<?php
declare(strict_types=1);

namespace Tests\App\Domain\ValueObject\Token\Action;

use App\Domain\Exception\InvalidTokenException;
use App\Domain\ValueObject\Token\Action\MoveShipToken;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Tests\App\Domain\ValueObject\Token\TokenTestCase;

class MoveShipTokenTest extends TokenTestCase
{
    private const UUID_EXAMPLE_SHIP = '00000000-0000-4000-0000-000000000000';
    private const UUID_EXAMPLE_CHANNEL = '00000000-0000-4000-0000-000000000001';
    private const UUID_EXAMPLE_USER = '00000000-0000-4000-0000-000000000002';
    private const UUID_EXAMPLE_PORT = '00000000-0000-4000-0000-000000000003';

    public function testInvalidTokenType()
    {
        $this->expectException(InvalidTokenException::class);
        new MoveShipToken($this->getMockInvalidTokenType());
    }

    public function testInvalidDueToNoShipId()
    {
        $tokenObject = new MoveShipToken($this->getMockToken(MoveShipToken::TYPE));

        // don't expect the exception until we call the method that would throw it
        $this->expectException(InvalidTokenException::class);
        $tokenObject->getShipId();
    }

    public function testInvalidDueToNoChannelId()
    {
        $tokenObject = new MoveShipToken($this->getMockToken(MoveShipToken::TYPE));

        // don't expect the exception until we call the method that would throw it
        $this->expectException(InvalidTokenException::class);
        $tokenObject->getChannelId();
    }

    public function testInvalidDueToNoDirection()
    {
        $tokenObject = new MoveShipToken($this->getMockToken(MoveShipToken::TYPE));

        // don't expect the exception until we call the method that would throw it
        $this->expectException(InvalidTokenException::class);
        $tokenObject->isReversed();
    }

    public function testTokenData()
    {
        $token = $this->getMockToken(MoveShipToken::TYPE, [
            MoveShipToken::KEY_CHANNEL => self::UUID_EXAMPLE_CHANNEL,
            MoveShipToken::KEY_SHIP => self::UUID_EXAMPLE_SHIP,
            MoveShipToken::KEY_OWNER => self::UUID_EXAMPLE_USER,
            MoveShipToken::KEY_JOURNEY_TIME => $time = 120,
            MoveShipToken::KEY_REVERSED => true,
        ]);

        $tokenObject = new MoveShipToken($token);

        $this->assertStandardTokenValues($token, $tokenObject);

        $this->assertInstanceOf(UuidInterface::class, $tokenObject->getChannelId());
        $this->assertSame(self::UUID_EXAMPLE_CHANNEL, (string) $tokenObject->getChannelId());

        $this->assertInstanceOf(UuidInterface::class, $tokenObject->getShipId());
        $this->assertSame(self::UUID_EXAMPLE_SHIP, (string) $tokenObject->getShipId());

        $this->assertInstanceOf(UuidInterface::class, $tokenObject->getOwnerId());
        $this->assertSame(self::UUID_EXAMPLE_USER, (string) $tokenObject->getOwnerId());

        $this->assertTrue($tokenObject->isReversed());
        $this->assertSame($time, $tokenObject->getJourneyTime());

        $serial = $tokenObject->jsonSerialize();
        $this->assertTrue(is_array($serial));
        $this->assertSame('ActionToken', $serial['type']);
        $this->assertSame('/actions/move-ship', $serial['path']);
        $this->assertSame(self::TOKEN_TOSTRING, $serial['token']);
    }

    public function testMakeClaims()
    {
        $claims = MoveShipToken::makeClaims(
            Uuid::fromString(self::UUID_EXAMPLE_SHIP),
            Uuid::fromString(self::UUID_EXAMPLE_CHANNEL),
            Uuid::fromString(self::UUID_EXAMPLE_USER),
            true,
            120,
            Uuid::fromString(self::UUID_EXAMPLE_PORT)
        );

        $this->assertTrue(is_array($claims));
        $this->assertSame(MoveShipToken::TYPE, $claims[MoveShipToken::KEY_TOKEN_TYPE]);

        $this->assertSame(self::UUID_EXAMPLE_SHIP, $claims[MoveShipToken::KEY_SHIP]);
        $this->assertSame(self::UUID_EXAMPLE_CHANNEL, $claims[MoveShipToken::KEY_CHANNEL]);
        $this->assertSame(self::UUID_EXAMPLE_USER, $claims[MoveShipToken::KEY_OWNER]);
        $this->assertSame(self::UUID_EXAMPLE_PORT, $claims[MoveShipToken::KEY_FIRST_PORT]);
        $this->assertSame(120, $claims[MoveShipToken::KEY_JOURNEY_TIME]);
        $this->assertTrue($claims[MoveShipToken::KEY_REVERSED]);
    }
}
