<?php
declare(strict_types=1);

namespace App\Controller\Security;

use App\Service\TokensService;
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

        $response = new JsonResponse('Hello uuid: ' . (string)$userId);

        return $response;
    }
}
