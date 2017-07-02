<?php
declare(strict_types = 1);
namespace App\Controller\Security\Traits;

use App\ApplicationTime;
use App\Config\TokenConfig;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\ValidationData;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

trait UserTokenTrait
{
    protected function getUserId(
        Request $request,
        TokenConfig $tokenConfig
    ): UuidInterface {

        $token = $this->getJWT($request, $tokenConfig);
        return Uuid::fromString($token->getClaim('userUUID'));
    }

    protected function makeWebTokenForUserId(
        TokenConfig $tokenConfig,
        UuidInterface $userId
    ): Token {
        $signer = new Sha256();
        $token = (new Builder())->setIssuer($tokenConfig->getIssuer())
        ->setAudience($tokenConfig->getAudience())
        ->setId($tokenConfig->getId(), true)
        ->setIssuedAt(ApplicationTime::getTime()->getTimestamp())
            ->setExpiration(ApplicationTime::getTime()->add(new \DateInterval('P2Y'))->getTimestamp())
            ->set('userUUID', (string) $userId)
            ->sign($signer, $tokenConfig->getPrivateKey())
            ->getToken();

        return $token;
    }

    protected function makeCookieForWebToken(TokenConfig $tokenConfig, Token $token): Cookie
    {
        $secureCookie = false; // todo - be true as often as possible
        return new Cookie(
            $tokenConfig->getCookieName(),
            $token,
            ApplicationTime::getTime()->add(new \DateInterval('P2Y')),
            '/',
            null,
            $secureCookie,
            true
        );
    }

    private function getJWT(
        Request $request,
        TokenConfig $tokenConfig
    ): ?Token {
        $tokenString = $request->cookies->get($tokenConfig->getCookieName());
        // todo - also possible to get it out of the Auth header
        if (!$tokenString) {
            throw new BadRequestHttpException('No credentials');
        }
        $token = (new Parser())->parse((string) $tokenString);

        $data = new ValidationData();
        $data->setIssuer($tokenConfig->getIssuer());
        $data->setAudience($tokenConfig->getAudience());
        $data->setId($tokenConfig->getId());
        $data->setCurrentTime(ApplicationTime::getTime()->getTimestamp());

        $signer = new Sha256();
        if (!$token->verify($signer, $tokenConfig->getPrivateKey()) ||
            !$token->validate($data)) {
            throw new AccessDeniedHttpException('Invalid credentials');
        }

        return $token;
    }
}
