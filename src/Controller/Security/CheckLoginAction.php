<?php
declare(strict_types=1);

namespace App\Controller\Security;

use App\Service\TokensService;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class CheckLoginAction
{
    use Traits\UserTokenTrait;

    public function __invoke(
        Request $request,
        TokensService $tokensService
    ): JsonResponse {

        $userId = $this->getUserId($request, $tokensService);

        $response = new JsonResponse([
            'id' => (string) $userId,
            'score' => [
                // todo - real values with a real Score object
                'value' => rand(0,1000),
                'rate' => round(rand(0,50)/9.8, 2),
                'datetime' => (new \DateTimeImmutable())->format('c'),
            ],
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
