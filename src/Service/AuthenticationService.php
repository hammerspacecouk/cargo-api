<?php
declare(strict_types=1);

namespace App\Service;

use App\Data\Database\Entity\AuthenticationToken;
use App\Data\TokenProvider;
use App\Domain\Entity\User;
use App\Domain\Entity\UserAuthentication;
use App\Domain\Exception\InvalidTokenException;
use App\Domain\ValueObject\AuthProvider;
use App\Domain\ValueObject\OauthState;
use App\Domain\ValueObject\Token\Action\RemoveAuthProviderToken;
use App\Domain\ValueObject\Token\SimpleDataToken\OauthLoginToken;
use App\Infrastructure\DateTimeFactory;
use DateInterval;
use DateTimeImmutable;
use Doctrine\ORM\Query;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;

class AuthenticationService extends AbstractService
{
    use Traits\CookieTrait;

    private const COOKIE_NAME = 'AUTHENTICATION_TOKEN';
    private const OAUTH_COOKIE_NAME = 'TEMP_LOCAL_AUTH_STATE';
    private const TOKEN_EXPIRY = 'P3M';
    private const WAIT_BEFORE_REFRESH = 'P7D';

    public function getOAuthStateCookie(string $stateId, string $redirectUrl): Cookie
    {
        $state = new OauthState($stateId, $redirectUrl);
        $token = $this->tokenHandler->makeToken(...OauthLoginToken::make($state->getClaims()));
        $data = (string)new OauthLoginToken($token);
        return $this->makeCookie(
            $data,
            self::OAUTH_COOKIE_NAME,
            DateTimeFactory::now()->add(new DateInterval('PT10M')),
            'none'
        );
    }

    public function parseOauthState(Request $request): OauthState
    {
        $stateId = $request->get('state');
        $cookie = $request->cookies->get(self::OAUTH_COOKIE_NAME);
        if (!$cookie || !$stateId) {
            throw new InvalidTokenException('No state cookie or state ID found');
        }

        $token = new OauthLoginToken($this->tokenHandler->parseTokenFromString($cookie, false));
        $state = OauthState::createFromClaims($token->getData());

        if ($state->getStateId() !== $stateId) {
            throw new InvalidTokenException('Invalid state');
        }

        return $state;
    }

    public function makeNewAuthenticationCookie(
        User $user
    ): Cookie {
        $expiry = DateTimeFactory::now()->add(new DateInterval(self::TOKEN_EXPIRY));
        $secret = \bin2hex(\random_bytes(32));
        $creationTime = DateTimeFactory::now();

        $digest = $this->getDigest($user->getId(), $creationTime, $secret);

        $userEntity = $this->entityManager->getUserRepo()->getByID($user->getId(), Query::HYDRATE_OBJECT);

        $tokenEntity = new AuthenticationToken(
            $creationTime,
            DateTimeFactory::now(),
            $expiry,
            $digest,
            $userEntity,
        );

        $this->entityManager->persist($tokenEntity);
        $this->entityManager->flush();

        $cookieToken = \bin2hex($tokenEntity->id->getBytes()) . $secret;
        return $this->makeCookie($cookieToken, self::COOKIE_NAME, new DateTimeImmutable('2038-01-01T12:00:00Z'));
    }


    public function makeRemovalCookie(): Cookie
    {
        return $this->makeCookie(
            '',
            self::COOKIE_NAME,
            DateTimeFactory::now()->sub(new DateInterval('P1Y')),
        );
    }

    public function getAuthenticationFromRequest(Request $request, bool $withRefresh = true): ?UserAuthentication
    {
        $token = $request->cookies->get(self::COOKIE_NAME);
        if (!$token) {
            return null;
        }

        $splitPoint = 32;

        // split the cookie token
        $id = \substr($token, 0, $splitPoint);
        $secret = \substr($token, $splitPoint);

        $id = Uuid::fromString($id);

        // get the row out of the database by ID (where not expired)
        $tokenEntity = $this->entityManager->getAuthenticationTokenRepo()->findUnexpiredById($id);

        if (!$tokenEntity) {
            return null;
        }

        $authentication = $this->mapperFactory->createUserAuthenticationMapper()
            ->getUserAuthentication($tokenEntity);

        // compare the hashes. if incorrect, return null
        $digest = $this->getDigest(
            $authentication->getUser()->getId(),
            $authentication->getCreationTime(),
            $secret,
        );

        if (!\hash_equals($tokenEntity['digest'], $digest)) {
            return null;
        }

        // to lower churn (and thundering herd), we'll only extend the token every so often (not every request)
        $timeToUpdate = $authentication->getLastUsed()->add(new DateInterval(self::WAIT_BEFORE_REFRESH));
        if ($withRefresh && $timeToUpdate < DateTimeFactory::now()) {
            $this->extendAuthentication($authentication);
        }

        return $authentication;
    }

    public function remove(UserAuthentication $userAuthentication): void
    {
        $this->entityManager->getAuthenticationTokenRepo()->deleteById($userAuthentication->getId());
    }

    /**
     * @param User $user
     * @return AuthProvider[]
     */
    public function getAuthProviders(User $user): array
    {
        $providers = [];

        /** @var \App\Data\Database\Entity\User $userEntity */
        $userEntity = $this->entityManager->getUserRepo()->getByID($user->getId(), Query::HYDRATE_OBJECT);

        if ($this->applicationConfig->isLoginGoogleEnabled()) {
            $providers[] = $this->setupAuthProvider(
                AuthProvider::PROVIDER_GOOGLE,
                $userEntity->googleId !== null,
                $user
            );
        }
        if ($this->applicationConfig->isLoginMicrosoftEnabled()) {
            $providers[] = $this->setupAuthProvider(
                AuthProvider::PROVIDER_MICROSOFT,
                $userEntity->microsoftId !== null,
                $user
            );
        }
        if ($this->applicationConfig->isLoginRedditEnabled()) {
            $providers[] = $this->setupAuthProvider(
                AuthProvider::PROVIDER_REDDIT,
                $userEntity->redditId !== null,
                $user
            );
        }

        return $providers;
    }

    public function parseRemoveAuthProviderToken(
        string $tokenString
    ): RemoveAuthProviderToken {
        return new RemoveAuthProviderToken($this->tokenHandler->parseTokenFromString($tokenString), $tokenString);
    }

    public function useRemoveAuthProviderToken(
        RemoveAuthProviderToken $tokenDetail
    ): void {
        $authProvider = $tokenDetail->getAuthProvider();
        $userId = $tokenDetail->getUserId();

        /** @var \App\Data\Database\Entity\User $userEntity */
        $userEntity = $this->entityManager->getUserRepo()->getByID($userId, Query::HYDRATE_OBJECT);
        $field = $authProvider . 'Id';
        $userEntity->$field = null;
        $this->entityManager->persist($userEntity);
        $this->entityManager->flush();
    }

    private function setupAuthProvider(string $provider, bool $isSetup, ?User $user): AuthProvider
    {
        $removalToken = null;

        if ($isSetup && $user) {
            $tokenData = $this->tokenHandler->makeToken(...RemoveAuthProviderToken::make(
                $user->getId(),
                $provider
            ));
            $removalToken = new RemoveAuthProviderToken(
                $tokenData,
                TokenProvider::getActionPath(RemoveAuthProviderToken::class)
            );
        }

        return new AuthProvider(
            $provider,
            $removalToken
        );
    }

    private function getDigest(UuidInterface $userId, DateTimeImmutable $creationTime, string $secret): string
    {
        return \hash_hmac(
            'sha256',
            \json_encode([
                $secret,
                $userId->toString(),
                $creationTime->getTimestamp(),
            ], JSON_THROW_ON_ERROR),
            $this->applicationConfig->getTokenPrivateKey(),
        );
    }

    private function extendAuthentication(UserAuthentication $authentication): void
    {
        /** @var AuthenticationToken $tokenEntity */
        $tokenEntity = $this->entityManager->getAuthenticationTokenRepo()->getByID(
            $authentication->getId(),
            Query::HYDRATE_OBJECT
        );

        $now = DateTimeFactory::now();
        $tokenEntity->expiry = $now->add(new DateInterval(self::TOKEN_EXPIRY));
        $tokenEntity->lastUsed = $now;
        $this->entityManager->persist($tokenEntity);
        $this->entityManager->flush();
    }
}
