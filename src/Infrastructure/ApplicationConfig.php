<?php
declare(strict_types=1);

namespace App\Infrastructure;

use DateInterval;
use ParagonIE\Paseto\Keys\Version2\SymmetricKey;

class ApplicationConfig
{
    private const LOGIN_ANON        = 0b000000000000001;
    private const LOGIN_GOOGLE      = 0b000000000000010;
    private const LOGIN_FACEBOOK    = 0b000000000000100;
    private const LOGIN_TWITTER     = 0b000000000001000;
    private const LOGIN_MICROSOFT   = 0b000000000010000;

    private $environment;
    private $hostnameApi;
    private $hostnameWeb;
    private $cookieScope;
    private $maxUsersPerIp;
    private $ipLifetimeSeconds;
    private $distanceMultiplier;
    private $emailFromName;
    private $emailFromAddress;
    private $tokenPrivateKey;
    private $version;
    private $applicationSecret;
    private $loginAnonEnabled;
    private $loginGoogleEnabled;
    private $loginFacebookEnabled;
    private $loginTwitterEnabled;
    private $loginMicrosoftEnabled;

    public function __construct(
        string $environment,
        string $hostnameApi,
        string $hostnameWeb,
        string $cookieScope,
        int $maxUsersPerIp,
        int $ipLifetimeSeconds,
        int $distanceMultiplier,
        string $emailFromName,
        string $emailFromAddress,
        string $applicationSecret,
        string $loginFlags,
        string $tokenPrivateKey,
        ?string $version
    ) {
        $this->environment = $environment;
        $this->hostnameApi = $hostnameApi;
        $this->hostnameWeb = $hostnameWeb;
        $this->cookieScope = $cookieScope;
        $this->maxUsersPerIp = (int)$maxUsersPerIp;
        $this->distanceMultiplier = (int)$distanceMultiplier;
        $this->emailFromName = $emailFromName;
        $this->emailFromAddress = $emailFromAddress;
        $this->tokenPrivateKey = $tokenPrivateKey;
        $this->version = $version;
        $this->applicationSecret = $applicationSecret;
        $this->ipLifetimeSeconds = $ipLifetimeSeconds;

        $flags = \bindec($loginFlags);
        $this->loginAnonEnabled = (bool)($flags & self::LOGIN_ANON);
        $this->loginGoogleEnabled = (bool)($flags & self::LOGIN_GOOGLE);
        $this->loginFacebookEnabled = (bool)($flags & self::LOGIN_FACEBOOK);
        $this->loginTwitterEnabled = (bool)($flags & self::LOGIN_TWITTER);
        $this->loginMicrosoftEnabled = (bool)($flags & self::LOGIN_MICROSOFT);
    }

    public function getEnvironment(): string
    {
        return $this->environment;
    }

    public function getApiHostname(): string
    {
        return $this->hostnameApi;
    }

    public function getWebHostname(): string
    {
        return $this->hostnameWeb;
    }

    public function getCookieScope(): string
    {
        return $this->cookieScope;
    }

    public function getMaxUsersPerIp(): int
    {
        return $this->maxUsersPerIp;
    }

    public function getIpLifetimeSeconds(): int
    {
        return $this->ipLifetimeSeconds;
    }

    public function getIpLifetimeInterval(): DateInterval
    {
        return new DateInterval('PT' . $this->ipLifetimeSeconds . 'S');
    }

    public function getDistanceMultiplier(): int
    {
        return $this->distanceMultiplier;
    }

    public function getEmailFromName(): string
    {
        return $this->emailFromName;
    }

    public function getEmailFromAddress(): string
    {
        return $this->emailFromAddress;
    }

    public function getTokenPrivateKey(): SymmetricKey
    {
        $keyBin = \hex2bin($this->tokenPrivateKey);
        if (!$keyBin) {
            throw new \RuntimeException('Private key cannot be converted to binary');
        }
        return new SymmetricKey($keyBin);
    }

    public function getApplicationSecret(): string
    {
        return $this->applicationSecret;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function isLoginAnonEnabled(): bool
    {
        return $this->loginAnonEnabled;
    }

    public function isLoginGoogleEnabled(): bool
    {
        return $this->loginGoogleEnabled;
    }

    public function isLoginFacebookEnabled(): bool
    {
        return $this->loginFacebookEnabled;
    }

    public function isLoginTwitterEnabled(): bool
    {
        return $this->loginTwitterEnabled;
    }

    public function isLoginMicrosoftEnabled(): bool
    {
        return $this->loginMicrosoftEnabled;
    }
}
