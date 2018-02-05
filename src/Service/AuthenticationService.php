<?php
declare(strict_types=1);

namespace App\Service;

use App\Data\Database\Entity\AuthenticationToken;
use App\Data\ID;
use App\Domain\Entity\User;
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

    public function makeNewAuthenticationCookie(User $user, string $deviceDescription): Cookie
    {
        $id = ID::makeNewID(AuthenticationToken::class);
        $expiry = $this->currentTime->add(new \DateInterval(self::COOKIE_EXPIRY));
        $secret = \bin2hex(\random_bytes(32));

        $digest = $this->getDigest($user->getId(), $expiry, $secret);

        $userEntity = $this->entityManager->getUserRepo()->getByID($user->getId(), Query::HYDRATE_OBJECT);

        $tokenEntity = new AuthenticationToken(
            $id,
            $this->currentTime,
            $expiry,
            $userEntity,
            $digest,
            $deviceDescription
        );
        $this->entityManager->persist($tokenEntity);
        $this->entityManager->flush();

        $token = \bin2hex($id->getBytes()) . $secret;

        return $this->makeCookie($token, self::COOKIE_NAME, $expiry);
    }

    public function getUserFromRequest(Request $request): ?User
    {
        $token = $request->cookies->get(self::COOKIE_NAME);
        if (!$token) {
            return null;
        }

        $splitPoint = 32;

        // split the cookie token
        $id = \substr($token, 0 , $splitPoint);
        $secret = \substr($token, $splitPoint);

        $id = Uuid::fromString($id);

        // get the row out of the database by ID (where not expired)
        $tokenEntity = $this->entityManager->getAuthenticationTokenRepo()->findUnexpiredById($id);

        // if not exist, return null
        if (!$tokenEntity) {
            return null;
        }

        $user = $this->mapperFactory->createUserMapper()->getUser($tokenEntity['user']);

        // compare the hashes. if incorrect, return null
        $digest = $this->getDigest(
            $user->getId(),
            DateTimeImmutable::createFromMutable($tokenEntity['expiry']),
            $secret
        );

        if ($tokenEntity['digest'] !== $digest) {
            return null;
        }

        return $user;
        // todo - if the expiry is < 2 months away, extend it (todo - how to return new cookie?)
    }

    private function getDigest(UuidInterface $userId, DateTimeImmutable $expiry, string $secret)
    {
        return \hash_hmac(
            'sha256',
            \json_encode([
                $secret,
                (string) $userId,
                $expiry->getTimestamp(),
            ]),
            $this->applicationConfig->getTokenPrivateKey()
        );
    }

    public function removeFromRequest($request)
    {
        // get the token from the request (ensuring it is correct according to the hash)
        // delete from the database
    }

}
