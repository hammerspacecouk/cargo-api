<?php
declare(strict_types=1);

namespace Tests\App\Domain\ValueObject\Token;

use App\Domain\Exception\InvalidTokenException;
use App\Domain\ValueObject\Token\AccessToken;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class AccessTokenTest extends TokenTestCase
{
    private const USER_UUID_EXAMPLE = '00000000-0000-4000-0000-000000000000';

    public function testInvalidTokenType()
    {
        $this->expectException(InvalidTokenException::class);
        new AccessToken($this->getMockInvalidTokenType());
    }

    public function testInvalidDueToNoUserId()
    {
        $tokenObject = new AccessToken($this->getMockToken(AccessToken::TYPE));

        // don't expect the exception until we call the method that would throw it
        $this->expectException(InvalidTokenException::class);
        $tokenObject->getUserId();
    }

    public function testTokenData()
    {
        $token = $this->getMockToken(AccessToken::TYPE, [
            AccessToken::KEY_USER_ID => self::USER_UUID_EXAMPLE,
        ]);

        $tokenObject = new AccessToken($token, $cookies = ['cookiesExampleData']);

        $this->assertStandardTokenValues($token, $tokenObject);
        $this->assertSame($cookies, $tokenObject->getCookies());
        $this->assertInstanceOf(UuidInterface::class, $tokenObject->getUserId());
        $this->assertSame(self::USER_UUID_EXAMPLE, (string)$tokenObject->getUserId());
    }

    public function testMakeClaims()
    {
        $userId = Uuid::fromString(self::USER_UUID_EXAMPLE);
        $claims = AccessToken::makeClaims($userId);

        $this->assertTrue(is_array($claims));

        $this->assertSame(AccessToken::TYPE, $claims[AccessToken::KEY_TOKEN_TYPE]);
        $this->assertSame(self::USER_UUID_EXAMPLE, $claims[AccessToken::KEY_USER_ID]);
    }
}
