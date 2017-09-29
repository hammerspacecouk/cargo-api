<?php
declare(strict_types=1);

namespace App\Controller\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class LoginAction
{
    use Traits\UserTokenTrait;

    public function __invoke(
        Request $request
    ): JsonResponse {
        return new JsonResponse(['google' => '/login/google']);
    }
}
