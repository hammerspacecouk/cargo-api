<?php
declare(strict_types=1);

namespace App\Controller\Security;

use App\Service\TokensService;
use App\Service\UsersService;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class CheckLoginAction
{
    use Traits\UserTokenTrait;

    public function __invoke(
        Request $request,
        TokensService $tokensService,
        UsersService $usersService
    ): JsonResponse {

        $user = $this->getUser($request, $tokensService, $usersService);

        $response = new JsonResponse([
            'id' => $user->getId(),
            'score' => $user->getScore(),
            'cookies' => array_map(function (Cookie $cookie) {
                $opts = [
                    'domain' => $cookie->getDomain(),
                    'httpOnly' => $cookie->isHttpOnly(),
                    'path' => $cookie->getPath(),
                    'secure' => $cookie->isSecure(),
                ];

                if ($cookie->getMaxAge() !== 0) {
                    $opts['maxAge'] = $cookie->getMaxAge() * 1000; // front end js expects milliseconds;
                }

                return [
                    'name' => $cookie->getName(),
                    'value' => $cookie->getValue(),
                    'opts' => $opts,
                ];
            }, $this->cookies),
        ]);

        return $this->userResponse($response);
    }
}
