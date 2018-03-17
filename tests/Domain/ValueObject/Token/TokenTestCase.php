<?php
declare(strict_types=1);

namespace Tests\App\Domain\ValueObject\Token;

use App\Domain\ValueObject\Token\AbstractToken;
use Lcobucci\JWT\Token;
use PHPUnit\Framework\MockObject\MockObject;
use Ramsey\Uuid\UuidInterface;

abstract class TokenTestCase extends \PHPUnit\Framework\TestCase
{
    protected const TOKEN_TO_STRING = 'tokenString';

    protected const TOKEN_EXPIRY_TIMESTAMP = 1504974245;
    protected const TOKEN_EXPIRY_DATETIME = '2017-09-09T16:24:05+0000';
    protected const TOKEN_UUID = '7d07139a-d9d9-47f8-895d-12d22ecc61d2';

    protected function getMockToken(string $type, array $claims = []): MockObject
    {
        $claims = array_merge([
            AbstractToken::KEY_TOKEN_TYPE => $type,
            AbstractToken::KEY_TOKEN_ID => self::TOKEN_UUID,
            AbstractToken::KEY_TOKEN_EXPIRY => self::TOKEN_EXPIRY_TIMESTAMP,
        ], $claims);

        return $this->getRawMockToken($claims);
    }

    protected function getRawMockToken(array $claims): MockObject
    {
        $token = $this->createMock(Token::class);

        $token->expects($this->any())
            ->method('hasClaim')
            ->willReturnCallback(function (string $key) use ($claims): bool {
                return array_key_exists($key, $claims);
            });

        $token->expects($this->any())
            ->method('getClaim')
            ->with($this->logicalOr(...array_keys($claims)))
            ->willReturnCallback(function (string $key) use ($claims) {
                return $claims[$key];
            });

        $token->expects($this->any())
            ->method('__toString')
            ->willReturn(self::TOKEN_TO_STRING);

        return $token;
    }

    protected function getMockInvalidTokenType(): MockObject
    {
        return $this->getRawMockToken([AbstractToken::KEY_TOKEN_TYPE => 'nope']);
    }

    protected function assertStandardTokenValues(Token $originalToken, AbstractToken $token): void
    {
        $this->assertSame($originalToken, $token->getOriginalToken());
        $this->assertSame(self::TOKEN_TO_STRING, (string)$token);

        $this->assertInstanceOf(\DateTimeImmutable::class, $token->getExpiry());
        $this->assertSame(self::TOKEN_EXPIRY_DATETIME, $token->getExpiry()->format(\DateTime::ISO8601));

        $this->assertInstanceOf(UuidInterface::class, $token->getId());
        $this->assertSame(self::TOKEN_UUID, $token->getId()->toString());
    }
}
