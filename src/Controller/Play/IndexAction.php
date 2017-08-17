<?php
declare(strict_types = 1);
namespace App\Controller\Play;

use App\Controller\Security\Traits\UserTokenTrait;
use App\Service\ShipsService;
use App\Service\TokensService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * The 'My' Section reads from your cookie, so is custom and un-cacheable
 */
class IndexAction
{
    use UserTokenTrait;

    public function __invoke(
        Request $request,
        TokensService $tokensService,
        ShipsService $shipsService,
        LoggerInterface $logger
    ): JsonResponse {

        $logger->info(__CLASS__);
        $userId = $this->getUserId($request, $tokensService);

        $ships = $shipsService->getForOwnerIDWithLocation($userId, 100);

        $status = [
            'userId' => $userId,
            'ships' => $ships,
        ];

        return $this->userResponse(new JsonResponse($status));
    }
}
