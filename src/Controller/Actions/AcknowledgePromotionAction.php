<?php
declare(strict_types=1);

namespace App\Controller\Actions;

use App\Service\PlayerRanksService;
use App\Service\UsersService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class AcknowledgePromotionAction
{
    private UsersService $usersService;
    private PlayerRanksService $playerRanksService;

    public function __construct(
        UsersService $usersService,
        PlayerRanksService $playerRanksService
    ) {
        $this->usersService = $usersService;
        $this->playerRanksService = $playerRanksService;
    }

    public function invoke(string $token): array
    {
        $acknowledgePromotionToken = $this->usersService->parseAcknowledgePromotionToken($token);
        $user = $this->usersService->getById($acknowledgePromotionToken->getUserId());
        if (!$user) {
            throw new BadRequestHttpException('Unpossible token');
        }

        $this->usersService->useAcknowledgePromotionToken($acknowledgePromotionToken);

        // send back the new rank status
        $data = [
            'rankStatus' => $this->playerRanksService->getForUser($user),
        ];
        return $data;
    }
}
