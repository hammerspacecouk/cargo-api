<?php
declare(strict_types = 1);
namespace App\Controller\Security;

use App\Data\TokenHandler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class CheckLoginAction
{
    use Traits\UserTokenTrait;

    public function __invoke(
        Request $request,
        TokenHandler $tokenHandler
    ): JsonResponse {

        $userId = $this->getUserId($request, $tokenHandler);

        $response = new JsonResponse('Hello uuid: ' . (string) $userId);

        return $response;
    }
}
