<?php
declare(strict_types = 1);
namespace App\Controller\Play;

use App\Config\TokenConfig;
use App\Controller\Security\Traits\UserTokenTrait;
use App\Data\TokenHandler;
use App\Service\ShipsService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * The 'My' Section reads from your cookie, so is custom and un-cacheable
 */
class IndexAction
{
    use UserTokenTrait;

    public function __invoke(
        Request $request,
        TokenConfig $tokenConfig,
        TokenHandler $tokensService,
        ShipsService $shipsService
    ): JsonResponse {
        $userId = $this->getUserIdReadOnly($request, $tokenConfig, $tokensService);

        $ships = $shipsService->getForOwnerIDWithLocation($userId, 100);

        $status = [
            'userId' => $userId,
            'ships' => $ships,
        ];

        return new JsonResponse($status);
    }
}
