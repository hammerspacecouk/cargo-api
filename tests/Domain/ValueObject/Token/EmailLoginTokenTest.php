<?php
declare(strict_types=1);

namespace Tests\App\Domain\ValueObject\Token;

use App\Domain\Exception\InvalidTokenException;
use App\Domain\ValueObject\EmailAddress;
use App\Domain\ValueObject\Token\EmailLoginToken;

class EmailLoginTokenTest extends TokenTestCase
{
    private const EMAIL_ADDRESS_EXAMPLE = 'test@example.com';

    public function testInvalidTokenType(): void
    {
        $this->expectException(InvalidTokenException::class);
        new EmailLoginToken($this->getMockInvalidTokenType());
    }

    public function testInvalidDueToNoEmailAddress(): void
    {
        $tokenObject = new EmailLoginToken($this->getMockToken(EmailLoginToken::SUBJECT));

        // don't expect the exception until we call the method that would throw it
        $this->expectException(InvalidTokenException::class);
        $tokenObject->getEmailAddress();
    }

    public function testTokenData(): void
    {
        $token = $this->getMockToken(EmailLoginToken::SUBJECT, [
            EmailLoginToken::KEY_EMAIL_ADDRESS => self::EMAIL_ADDRESS_EXAMPLE,
        ]);

        $tokenObject = new EmailLoginToken($token);

        $this->assertStandardTokenValues($token, $tokenObject);
        $this->assertEquals(new EmailAddress(self::EMAIL_ADDRESS_EXAMPLE), $tokenObject->getEmailAddress());
    }

    public function testMakeClaims(): void
    {
        $claims = EmailLoginToken::makeClaims(new EmailAddress(self::EMAIL_ADDRESS_EXAMPLE));

        $this->assertTrue(is_array($claims));
        $this->assertSame(EmailLoginToken::SUBJECT, $claims[EmailLoginToken::KEY_TOKEN_TYPE]);
        $this->assertSame(self::EMAIL_ADDRESS_EXAMPLE, $claims[EmailLoginToken::KEY_EMAIL_ADDRESS]);
    }
}
