<?php
declare(strict_types=1);

namespace App\Controller\Profile;

use App\Controller\CacheControlResponseTrait;
use App\Controller\ReferrerRedirectResponseTrait;
use App\Domain\Exception\InvalidTokenException;
use App\Infrastructure\ApplicationConfig;
use App\Service\AuthenticationService;
use App\Service\PlayerRanksService;
use App\Service\UsersService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Routing\Route;

class AcknowledgePromotionAction
{
    use CacheControlResponseTrait;
    use ReferrerRedirectResponseTrait;

    public static function getRouteDefinition(): Route
    {
        return new Route('/profile/acknowledge-promotion', [
            '_controller' => self::class,
        ], [], [], '', [], ['POST']);
    }

    public function __construct(
        private AuthenticationService $authenticationService,
        private ApplicationConfig $applicationConfig,
        private PlayerRanksService $playerRanksService,
        private UsersService $usersService,
        private LoggerInterface $logger
    ) {
    }

    public function __invoke(
        Request $request
    ): Response {

        $token = $request->get('token');
        if (empty($token)) {
            throw new BadRequestHttpException('Missing Token');
        }

        try {
            $acknowledgePromotionToken = $this->usersService->parseAcknowledgePromotionToken($token);
        } catch (InvalidTokenException $exception) {
            throw new BadRequestException($exception->getMessage());
        }
        $user = $this->usersService->getById($acknowledgePromotionToken->getUserId());
        if (!$user) {
            throw new BadRequestHttpException('Unpossible token');
        }

        $marketHistory = (int)$request->get('set_history', 0);
        $marketDiscovery = (int)$request->get('set_discovery', 0);
        $marketEconomy = (int)$request->get('set_economy', 0);
        $marketMilitary = (int)$request->get('set_military', 0);
        $total = $marketHistory + $marketDiscovery + $marketEconomy + $marketMilitary;

        if ($total > $acknowledgePromotionToken->getAvailableCredits()) {
            throw new ConflictHttpException('Trying to use more credits than you are allowed!! Tsk Tsk!');
        }

        $this->usersService->useAcknowledgePromotionToken(
            $acknowledgePromotionToken,
            $marketHistory,
            $marketDiscovery,
            $marketEconomy,
            $marketMilitary,
        );

        $response = $this->getReferrerRedirectResponse($request, $this->applicationConfig, '/play/profile');
        return $this->noCacheResponse($response);
    }
}
