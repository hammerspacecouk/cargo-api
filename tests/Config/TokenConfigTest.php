<?php declare(strict_types=1);

namespace Tests\App\Config;

use App\Config\TokenConfig;

class TokenConfigTest extends \PHPUnit\Framework\TestCase
{
    public function testValues()
    {
        $config = new TokenConfig(
            $privateKey = 'aaaabbbbb',
            $issuer = 'https://example.com',
            $cookieScope = '*.www.example.com'
        );

        $this->assertSame($privateKey, $config->getPrivateKey());
        $this->assertSame($issuer, $config->getIssuer());
        $this->assertSame($cookieScope, $config->getCookieScope());
    }
}
