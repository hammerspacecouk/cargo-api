<?php
declare(strict_types=1);

namespace App\Service;

use App\Data\Database\Entity\AuthenticationToken;
use App\Data\ID;
use App\Domain\Entity\User;
use App\Domain\Entity\UserAuthentication;
use App\Domain\ValueObject\EmailAddress;
use App\Domain\ValueObject\Token\EmailLoginToken;
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
    private const COOKIE_EXPIRY = 'P3M';
    private const EXPIRY_EMAIL_LOGIN = 'PT1H';
    private const WAIT_BEFORE_REFRESH = 'PT1H';

    public function makeNewAuthenticationCookie(
        User $user,
        string $deviceDescription,
        string $ipAddress,
        ?DateTimeImmutable $creationTime = null,
        ?UserAuthentication $previousToken = null
    ): Cookie {
        $id = ID::makeNewID(AuthenticationToken::class);
        $expiry = $this->currentTime->add(new \DateInterval(self::COOKIE_EXPIRY));
        $secret = \bin2hex(\random_bytes(32));
        if (!$creationTime) {
            $creationTime = $this->currentTime;
        }

        $ipAddress = (string) filter_var($ipAddress, FILTER_VALIDATE_IP);

        $digest = $this->getDigest($user->getId(), $expiry, $secret);

        $userEntity = $this->entityManager->getUserRepo()->getByID($user->getId(), Query::HYDRATE_OBJECT);

        $tokenEntity = new AuthenticationToken(
            $id,
            $creationTime,
            $this->currentTime,
            $expiry,
            $digest,
            $deviceDescription,
            $ipAddress,
            $userEntity
        );

        $this->entityManager->getConnection()->beginTransaction();
        try {
            $this->entityManager->persist($tokenEntity);
            if ($previousToken) {
                $this->entityManager->getAuthenticationTokenRepo()->deleteById($previousToken->getId());
            }
            $this->entityManager->flush();
            $this->entityManager->getConnection()->commit();
        } catch (\Throwable $e) {
            $this->entityManager->getConnection()->rollBack();
            $this->logger->error('Failed to renew authentication token');
            throw $e;
        }

        $token = \bin2hex($id->getBytes()) . $secret;
        return $this->makeCookie($token, self::COOKIE_NAME, $expiry);
    }

    public function makeRemovalCookie(): Cookie
    {
        return $this->makeCookie(
            '',
            self::COOKIE_NAME,
            $this->currentTime->sub(new DateInterval('P1Y'))
        );
    }

    public function getUpdatedCookieForResponse(
        UserAuthentication $currentAuthentication,
        string $ipAddress = ''
    ): ?Cookie {
        // to lower churn (and thundering herd), we'll only update the token every so often (not every request)
        $timeToUpdate = $currentAuthentication->getLastUsed()->add(new DateInterval(self::WAIT_BEFORE_REFRESH));
        if ($timeToUpdate > $this->currentTime) {
            return null;
        }

        return $this->makeNewAuthenticationCookie(
            $currentAuthentication->getUser(),
            $currentAuthentication->getDescription(),
            $ipAddress,
            $currentAuthentication->getCreationTime(),
            $currentAuthentication
        );
    }

    public function getAuthenticationFromRequest(Request $request): ?UserAuthentication
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

        // if not exist, return null
        if (!$tokenEntity) {
            return null;
        }

        $authentication = $this->mapperFactory->createUserAuthenticationMapper()
            ->getUserAuthentication($tokenEntity);

        // compare the hashes. if incorrect, return null
        $digest = $this->getDigest(
            $authentication->getUser()->getId(),
            DateTimeImmutable::createFromMutable($tokenEntity['expiry']),
            $secret
        );

        if (!\hash_equals($tokenEntity['digest'], $digest)) {
            return null;
        }

        return $authentication;
    }

    public function remove(UserAuthentication $userAuthentication): void
    {
        $this->entityManager->getAuthenticationTokenRepo()->deleteById($userAuthentication->getId());
    }

    public function findAllForUser(User $user)
    {
        $results = $this->entityManager->getAuthenticationTokenRepo()->findAllForUserId($user->getId());

        $mapper = $this->mapperFactory->createUserAuthenticationMapper();
        return array_map(function ($result) use ($mapper) {
            return $mapper->getUserAuthentication($result);
        }, $results);
    }

    public function makeEmailLoginToken(
        EmailAddress $emailAddress
    ): EmailLoginToken {
        $token = $this->tokenHandler->makeToken(
            EmailLoginToken::makeClaims(
                $emailAddress
            ),
            self::EXPIRY_EMAIL_LOGIN
        );
        return new EmailLoginToken($token);
    }

    public function useEmailLoginToken(
        string $tokenString
    ): EmailLoginToken {
        $token = $this->tokenHandler->parseTokenFromString($tokenString);
        $this->tokenHandler->markAsUsed($token);
        return new EmailLoginToken($token);
    }

    private function getDigest(UuidInterface $userId, DateTimeImmutable $expiry, string $secret)
    {
        return \hash_hmac(
            'sha256',
            \json_encode([
                $secret,
                (string)$userId,
                $expiry->getTimestamp(),
            ]),
            (string)$this->applicationConfig->getTokenPrivateKey()
        );
    }
}
