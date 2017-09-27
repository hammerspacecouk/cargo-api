<?php
declare(strict_types=1);

namespace Tests\App\Domain\ValueObject\Token;

use App\Domain\Exception\InvalidTokenException;
use App\Domain\ValueObject\Token\RefreshToken;

class RefreshTokenTest extends TokenTestCase
{
    private const ACCESS_KEY_EXAMPLE = 'abcdefghijklmnopqrstuvwxyz';
    private const ACCESS_KEY_EXAMPLE_DIGEST = '$2y$10$20/NqGsh/XfMlGrm18.RSONWZNxIppeKouLjNHyY973ySZ0ghjB86';

    public function testInvalidTokenType()
    {
        $this->expectException(InvalidTokenException::class);
        new RefreshToken($this->getMockInvalidTokenType());
    }

    public function testInvalidDueToNoAccessKey()
    {
        $accessToken = new RefreshToken($this->getMockToken(RefreshToken::TYPE));

        // don't expect the exception until we call the method that would throw it
        $this->expectException(InvalidTokenException::class);
        $accessToken->getAccessKey();
    }

    public function testTokenData()
    {
        $token = $this->getMockToken(RefreshToken::TYPE, [
            RefreshToken::KEY_ACCESS_KEY => self::ACCESS_KEY_EXAMPLE,
        ]);

        $accessToken = new RefreshToken($token);

        $this->assertStandardTokenValues($token, $accessToken);
        $this->assertSame(self::ACCESS_KEY_EXAMPLE, $accessToken->getAccessKey());

        $this->assertTrue($accessToken->validateAccessKey(self::ACCESS_KEY_EXAMPLE_DIGEST));
    }

    public function testInvalidAccessKey()
    {
        $accessToken = new RefreshToken($this->getMockToken(RefreshToken::TYPE, [
            RefreshToken::KEY_ACCESS_KEY => self::ACCESS_KEY_EXAMPLE,
        ]));

        $this->expectException(InvalidTokenException::class);
        $accessToken->validateAccessKey('notCorrect');
    }

    public function testMakeClaims()
    {
        $claims = RefreshToken::makeClaims(self::ACCESS_KEY_EXAMPLE);

        $this->assertTrue(is_array($claims));
        $this->assertSame(RefreshToken::TYPE, $claims[RefreshToken::KEY_TOKEN_TYPE]);
        $this->assertSame(self::ACCESS_KEY_EXAMPLE, $claims[RefreshToken::KEY_ACCESS_KEY]);
    }

    public function testMakeDigest()
    {
        // salt is randomised so I need to assert by checking it was something that can be parsed by password_verify
        $digest = RefreshToken::secureAccessKey(self::ACCESS_KEY_EXAMPLE);
        $this->assertTrue(password_verify(self::ACCESS_KEY_EXAMPLE, $digest));
    }
}
