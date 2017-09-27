<?php
declare(strict_types=1);

namespace Tests\App\Domain\ValueObject\Token\Action;

use App\Domain\Exception\InvalidTokenException;
use App\Domain\ValueObject\Token\Action\RenameShipToken;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Tests\App\Domain\ValueObject\Token\TokenTestCase;

class RenameShipTokenTest extends TokenTestCase
{
    private const UUID_EXAMPLE_SHIP = '00000000-0000-4000-0000-000000000000';
    private const EXAMPLE_SHIP_NAME = 'The Jolly Roger';

    public function testInvalidTokenType()
    {
        $this->expectException(InvalidTokenException::class);
        new RenameShipToken($this->getMockInvalidTokenType());
    }

    public function testInvalidDueToNoShipId()
    {
        $tokenObject = new RenameShipToken($this->getMockToken(RenameShipToken::TYPE));

        // don't expect the exception until we call the method that would throw it
        $this->expectException(InvalidTokenException::class);
        $tokenObject->getShipId();
    }

    public function testInvalidDueToNoName()
    {
        $tokenObject = new RenameShipToken($this->getMockToken(RenameShipToken::TYPE));

        // don't expect the exception until we call the method that would throw it
        $this->expectException(InvalidTokenException::class);
        $tokenObject->getShipName();
    }

    public function testTokenData()
    {
        $token = $this->getMockToken(RenameShipToken::TYPE, [
            RenameShipToken::KEY_SHIP_ID => self::UUID_EXAMPLE_SHIP,
            RenameShipToken::KEY_SHIP_NAME => self::EXAMPLE_SHIP_NAME
        ]);

        $tokenObject = new RenameShipToken($token);

        $this->assertStandardTokenValues($token, $tokenObject);

        $this->assertInstanceOf(UuidInterface::class, $tokenObject->getShipId());
        $this->assertSame(self::UUID_EXAMPLE_SHIP, (string) $tokenObject->getShipId());

        $this->assertSame(self::EXAMPLE_SHIP_NAME, $tokenObject->getShipName());

        $serial = $tokenObject->jsonSerialize();
        $this->assertTrue(is_array($serial));
        $this->assertSame('ActionToken', $serial['type']);
        $this->assertSame('/actions/rename-ship', $serial['path']);
        $this->assertSame(self::TOKEN_TOSTRING, $serial['token']);
    }

    public function testMakeClaims()
    {
        $claims = RenameShipToken::makeClaims(
            Uuid::fromString(self::UUID_EXAMPLE_SHIP),
            self::EXAMPLE_SHIP_NAME
        );

        $this->assertTrue(is_array($claims));
        $this->assertSame(RenameShipToken::TYPE, $claims[RenameShipToken::KEY_TOKEN_TYPE]);

        $this->assertSame(self::UUID_EXAMPLE_SHIP, $claims[RenameShipToken::KEY_SHIP_ID]);
        $this->assertSame(self::EXAMPLE_SHIP_NAME, $claims[RenameShipToken::KEY_SHIP_NAME]);
    }
}
