<?php
declare(strict_types=1);

namespace App\Infrastructure;

use DateInterval;
use Stripe\Stripe;

class ApplicationConfig
{
    private const LOGIN_ANON        = 1;
    private const LOGIN_GOOGLE      = 1 << 1;
    private const LOGIN_FACEBOOK    = 1 << 2;
    private const LOGIN_TWITTER     = 1 << 3;
    private const LOGIN_MICROSOFT   = 1 << 4;
    private const LOGIN_REDDIT      = 1 << 5;

    private string $environment;
    private string $hostnameApi;
    private string $hostnameWeb;
    private string $cookieScope;
    private int $maxUsersPerIp;
    private int $ipLifetimeSeconds;
    private int $distanceMultiplier;
    private string $emailFromName;
    private string $emailFromAddress;
    private string $tokenPrivateKey;
    private ?string $version;
    private string $applicationSecret;
    private bool $loginAnonEnabled;
    private bool $loginGoogleEnabled;
    private bool $loginFacebookEnabled;
    private bool $loginTwitterEnabled;
    private bool $loginMicrosoftEnabled;
    private bool $loginRedditEnabled;
    private string $stripeWebhookKey;

    public function __construct(
        string $environment,
        string $hostnameApi,
        string $hostnameWeb,
        string $cookieScope,
        string $maxUsersPerIp,
        string $ipLifetimeSeconds,
        string $distanceMultiplier,
        string $emailFromName,
        string $emailFromAddress,
        string $applicationSecret,
        string $loginFlags,
        string $tokenPrivateKey,
        string $stripeApiKey,
        string $stripeWebhookKey,
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
        $this->ipLifetimeSeconds = (int)$ipLifetimeSeconds;

        $flags = \bindec($loginFlags);
        $this->loginAnonEnabled = (bool)($flags & self::LOGIN_ANON);
        $this->loginGoogleEnabled = (bool)($flags & self::LOGIN_GOOGLE);
        $this->loginFacebookEnabled = (bool)($flags & self::LOGIN_FACEBOOK);
        $this->loginTwitterEnabled = (bool)($flags & self::LOGIN_TWITTER);
        $this->loginMicrosoftEnabled = (bool)($flags & self::LOGIN_MICROSOFT);
        $this->loginRedditEnabled = (bool)($flags & self::LOGIN_REDDIT);

        Stripe::setApiKey($stripeApiKey);
        $this->stripeWebhookKey = $stripeWebhookKey;
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

    public function getTokenPrivateKey(): string
    {
        return $this->tokenPrivateKey;
    }

    public function getApplicationSecret(): string
    {
        return $this->applicationSecret;
    }

    public function getVersion(): ?string
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

    public function isLoginRedditEnabled(): bool
    {
        return $this->loginRedditEnabled;
    }

    public function getStripeWebhookKey(): string
    {
        return $this->stripeWebhookKey;
    }
}
