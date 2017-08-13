<?php
declare(strict_types = 1);
namespace App\Controller\Security\Traits;

use App\ApplicationTime;
use App\Config\TokenConfig;
use App\Data\TokenHandler;
use App\Domain\Exception\InvalidTokenException;
use App\Domain\ValueObject\Token\UserIDToken;
use Lcobucci\JWT\Token;
use Ramsey\Uuid\Exception\InvalidUuidStringException;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

trait UserTokenTrait
{
    protected function getUserId(
        Request $request,
        TokenHandler $tokenHandler
    ): UuidInterface {
        try {
            $token = $tokenHandler->getRefreshToken($request);

            $userIdToken = new UserIDToken($token);
        } catch (InvalidTokenException | InvalidUuidStringException $e) {
            throw new AccessDeniedHttpException('Token Invalid: ' . $e->getMessage());
        }

        return $userIdToken->getUuid();
    }
}
