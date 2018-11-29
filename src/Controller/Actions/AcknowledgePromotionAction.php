<?php
declare(strict_types=1);

namespace App\Controller\Actions;

use App\Domain\ValueObject\Token\Action\AcknowledgePromotionToken;
use App\Service\PlayerRanksService;
use App\Service\UsersService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class AcknowledgePromotionAction extends AbstractAction
{
    private $usersService;
    private $playerRanksService;

    public static function getRouteDefinition(): array
    {
        return self::buildRouteDefinition(AcknowledgePromotionToken::class);
    }

    public function __construct(
        UsersService $usersService,
        PlayerRanksService $playerRanksService,
        LoggerInterface $logger
    ) {
        parent::__construct($logger);
        $this->usersService = $usersService;
        $this->playerRanksService = $playerRanksService;
    }

    public function invoke(string $token): array
    {
        $acknowledgePromtionToken = $this->usersService->parseAcknowledgePromotionToken($token);
        $user = $this->usersService->getById($acknowledgePromtionToken->getUserId());
        if (!$user) {
            throw new BadRequestHttpException('Unpossible token');
        }

        $this->usersService->useAcknowledgePromotionToken($acknowledgePromtionToken);

        // send back the new rank status
        $data = [
            'rankStatus' => $this->playerRanksService->getForUser($user),
        ];
        return $data;
    }
}
