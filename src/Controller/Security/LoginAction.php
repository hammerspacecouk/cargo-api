<?php
declare(strict_types = 1);
namespace App\Controller\Security;

use App\TokenConfig;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\{
    Cookie, JsonResponse, Request
};
use Symfony\Component\HttpKernel\Exception\{
    AccessDeniedHttpException, BadRequestHttpException
};

class LoginAction
{
    use Traits\UserTokenTrait;

    private const TEMP_USERS = [
        'david' => [
            'name' => 'DJM',
            'uuid' => '12345678-ca4e-4672-985c-bd4af53e8e93'
        ],
        'bob'=> [
            'name' => 'Bobertson',
            'uuid' => '98765432-ca4e-4672-985c-bd4af53e8e93'
        ],
    ];

    public function __invoke(
        Request $request,
        TokenConfig $tokenConfig
    ): JsonResponse {
        $username = $request->get('username');
        if (!$username) {
            throw new BadRequestHttpException('No user provided');
        }

        // look up the user details
        if (!isset(self::TEMP_USERS[$username])) {
            throw new AccessDeniedHttpException('Invalid credentials');
        }

        // generate a new token
        $userId = Uuid::fromString(self::TEMP_USERS[$username]['uuid']);

        $token = $this->makeWebTokenForUserId(
            $tokenConfig,
            $userId
        );

        $response = new JsonResponse([
            'token' => (string) $token
        ]);
        $response->headers->setCookie($this->makeCookieForWebToken($tokenConfig, $token));

        return $response;
    }
}
