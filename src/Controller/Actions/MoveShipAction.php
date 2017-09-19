<?php declare(strict_types=1);

namespace App\Controller\Actions;

use App\Service\TokensService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class MoveShipAction extends AbstractAction
{
    // general status and stats of the game as a whole
    public function __invoke(
        Request $request,
        TokensService $tokensService,
        LoggerInterface $logger
    ): JsonResponse {
        $logger->debug(__CLASS__);

        $tokenString = $this->getTokenDataFromRequest($request);
        $tokensService->useMoveShipToken($tokenString);

        return $this->actionResponse(new JsonResponse(['status' => 'ok']));
    }
}
