<?php
declare(strict_types = 1);
namespace App\Controller\Security;

use App\TokenConfig;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class CheckLoginAction
{
    use Traits\UserTokenTrait;

    public function __invoke(
        Request $request,
        TokenConfig $tokenConfig
    ): JsonResponse {

        $userId = $this->getUserId($request, $tokenConfig);

        $response = new JsonResponse('Hello uuid: ' . (string) $userId);

        return $response;
    }
}
