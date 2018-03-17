<?php
declare(strict_types=1);

namespace Tests\App\Infrastructure;

use App\Infrastructure\ApplicationConfig;

class ApplicationConfigTest extends \PHPUnit\Framework\TestCase
{
    public function testValues(): void
    {
        $config = new ApplicationConfig(
            $environment = 'alpha',
            $hostnameApi = 'api.www.example.com',
            $hostnameWeb = 'www.example.com',
            $cookieScope = '*.www.example.com',
            $distanceMultiplier = 10.0,
            $fromName = 'From',
            $fromAddress = 'from@example.com',
            $secret = 'aaaaaaaaaabbbbbbbbbbccccccccccdd',
            $tokenPrivateKey = 'aaaabbbbb',
            $tokenIssuer = 'https://example.com',
            $version = 'vvvvv'
        );

        $this->assertSame($environment, $config->getEnvironment());
        $this->assertSame($hostnameApi, $config->getApiHostname());
        $this->assertSame($hostnameWeb, $config->getWebHostname());
        $this->assertSame($cookieScope, $config->getCookieScope());
        $this->assertSame($distanceMultiplier, $config->getDistanceMultiplier());
        $this->assertSame($fromName, $config->getEmailFromName());
        $this->assertSame($fromAddress, $config->getEmailFromAddress());
        $this->assertSame($secret, $config->getApplicationSecret());
        $this->assertSame($tokenPrivateKey, $config->getTokenPrivateKey());
        $this->assertSame($tokenIssuer, $config->getTokenIssuer());
    }
}
