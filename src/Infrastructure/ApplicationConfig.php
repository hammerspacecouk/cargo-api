<?php
declare(strict_types=1);

namespace App\Infrastructure;

use DateInterval;
use ParagonIE\Paseto\Keys\Version2\SymmetricKey;

class ApplicationConfig
{
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
    private $loginEmailEnabled;

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
        bool $loginAnonEnabled,
        bool $loginGoogleEnabled,
        bool $loginFacebookEnabled,
        bool $loginTwitterEnabled,
        bool $loginMicrosoftEnabled,
        bool $loginEmailEnabled,
        string $tokenPrivateKey,
        ?string $version
    ) {
        $this->environment = $environment;
        $this->hostnameApi = $hostnameApi;
        $this->hostnameWeb = $hostnameWeb;
        $this->cookieScope = $cookieScope;
        $this->maxUsersPerIp = $maxUsersPerIp;
        $this->distanceMultiplier = $distanceMultiplier;
        $this->emailFromName = $emailFromName;
        $this->emailFromAddress = $emailFromAddress;
        $this->tokenPrivateKey = $tokenPrivateKey;
        $this->version = $version;
        $this->applicationSecret = $applicationSecret;
        $this->ipLifetimeSeconds = $ipLifetimeSeconds;
        $this->loginAnonEnabled = $loginAnonEnabled;
        $this->loginGoogleEnabled = $loginGoogleEnabled;
        $this->loginFacebookEnabled = $loginFacebookEnabled;
        $this->loginTwitterEnabled = $loginTwitterEnabled;
        $this->loginMicrosoftEnabled = $loginMicrosoftEnabled;
        $this->loginEmailEnabled = $loginEmailEnabled;
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
        return new SymmetricKey(\hex2bin($this->tokenPrivateKey));
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

    public function isLoginEmailEnabled(): bool
    {
        return $this->loginEmailEnabled;
    }
}
