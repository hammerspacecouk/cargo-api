<?php
declare(strict_types=1);

namespace App\Controller\TokenAction;

use App\Controller\Actions\AcknowledgePromotionAction;
use App\Controller\Actions\AddHealthAction;
use App\Controller\Actions\ApplyOffenceEffectAction;
use App\Controller\Actions\Effects\ApplyShipDefenceEffectAction;
use App\Controller\Actions\Effects\ApplyShipTravelEffectAction;
use App\Controller\Actions\JoinConvoyAction;
use App\Controller\Actions\LeaveConvoyAction;
use App\Controller\Actions\MoveShipAction;
use App\Controller\Actions\PortActions\DropCrateAction;
use App\Controller\Actions\PortActions\PickupCrateAction;
use App\Controller\Actions\PurchaseEffectAction;
use App\Controller\Actions\PurchaseShipAction;
use App\Controller\Actions\RemoveAuthProviderAction;
use App\Controller\Actions\RenameShipAction;
use App\Controller\Actions\RequestShipNameAction;
use App\Controller\Actions\SellShipAction;
use App\Controller\CacheControlResponseTrait;
use App\Data\TokenProvider;
use App\Domain\Exception\IllegalMoveException;
use App\Domain\Exception\TokenException;
use App\Domain\ValueObject\Token\Action\AcknowledgePromotionToken;
use App\Domain\ValueObject\Token\Action\ApplyEffect\ShipDefenceEffectToken;
use App\Domain\ValueObject\Token\Action\ApplyEffect\ShipTravelEffectToken;
use App\Domain\ValueObject\Token\Action\JoinConvoyToken;
use App\Domain\ValueObject\Token\Action\LeaveConvoyToken;
use App\Domain\ValueObject\Token\Action\MoveCrate\DropCrateToken;
use App\Domain\ValueObject\Token\Action\MoveCrate\PickupCrateToken;
use App\Domain\ValueObject\Token\Action\MoveShipToken;
use App\Domain\ValueObject\Token\Action\PurchaseEffectToken;
use App\Domain\ValueObject\Token\Action\PurchaseShipToken;
use App\Domain\ValueObject\Token\Action\RemoveAuthProviderToken;
use App\Domain\ValueObject\Token\Action\RenameShipToken;
use App\Domain\ValueObject\Token\Action\RequestShipNameToken;
use App\Domain\ValueObject\Token\Action\SellShipToken;
use App\Domain\ValueObject\Token\Action\ShipHealthToken;
use App\Domain\ValueObject\Token\Action\UseOffenceEffectToken;
use App\Infrastructure\DateTimeFactory;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Routing\Route;
use function App\Functions\DateTimes\jsonDecode;

class TokenAction
{
    use CacheControlResponseTrait;

    private DateTimeImmutable $now;
    private DateTimeImmutable $yesterday;
    private LoggerInterface $logger;
    private AcknowledgePromotionAction $acknowledgePromotionAction;
    private AddHealthAction $addHealthAction;
    private ApplyOffenceEffectAction $applyOffenceEffectAction;
    private MoveShipAction $moveShipAction;
    private PurchaseEffectAction $purchaseEffectAction;
    private PurchaseShipAction $purchaseShipAction;
    private RenameShipAction $renameShipAction;
    private RequestShipNameAction $requestShipNameAction;
    private ApplyShipDefenceEffectAction $applyShipDefenceEffectAction;
    private ApplyShipTravelEffectAction $applyShipTravelEffectAction;
    private DropCrateAction $dropCrateAction;
    private PickupCrateAction $pickupCrateAction;
    private RemoveAuthProviderAction $removeAuthProviderAction;
    private JoinConvoyAction $joinConvoyAction;
    private LeaveConvoyAction $leaveConvoyAction;
    private SellShipAction $sellShipAction;

    public static function getRouteDefinition(): Route
    {
        return new Route('/token', [
            '_controller' => self::class,
        ], [], [], '', [], ['POST']);
    }

    public function __construct(
        DateTimeFactory $dateTimeFactory,
        LoggerInterface $logger,
        AcknowledgePromotionAction $acknowledgePromotionAction,
        AddHealthAction $addHealthAction,
        ApplyOffenceEffectAction $applyOffenceEffectAction,
        JoinConvoyAction $joinConvoyAction,
        LeaveConvoyAction $leaveConvoyAction,
        MoveShipAction $moveShipAction,
        PurchaseEffectAction $purchaseEffectAction,
        PurchaseShipAction $purchaseShipAction,
        SellShipAction $sellShipAction,
        RenameShipAction $renameShipAction,
        RequestShipNameAction $requestShipNameAction,
        ApplyShipDefenceEffectAction $applyShipDefenceEffectAction,
        ApplyShipTravelEffectAction $applyShipTravelEffectAction,
        DropCrateAction $dropCrateAction,
        PickupCrateAction $pickupCrateAction,
        RemoveAuthProviderAction $removeAuthProviderAction
    ) {
        $this->logger = $logger;
        $this->acknowledgePromotionAction = $acknowledgePromotionAction;
        $this->addHealthAction = $addHealthAction;
        $this->applyOffenceEffectAction = $applyOffenceEffectAction;
        $this->moveShipAction = $moveShipAction;
        $this->purchaseEffectAction = $purchaseEffectAction;
        $this->purchaseShipAction = $purchaseShipAction;
        $this->renameShipAction = $renameShipAction;
        $this->requestShipNameAction = $requestShipNameAction;
        $this->applyShipDefenceEffectAction = $applyShipDefenceEffectAction;
        $this->applyShipTravelEffectAction = $applyShipTravelEffectAction;
        $this->dropCrateAction = $dropCrateAction;
        $this->pickupCrateAction = $pickupCrateAction;

        $this->now = $dateTimeFactory->now();
        $this->yesterday = $this->now->sub(new \DateInterval('P1D'));
        $this->removeAuthProviderAction = $removeAuthProviderAction;
        $this->joinConvoyAction = $joinConvoyAction;
        $this->leaveConvoyAction = $leaveConvoyAction;
        $this->sellShipAction = $sellShipAction;
    }

    public function __invoke(
        Request $request
    ): Response {
        $fullTokenString = $this->getTokenString($request);
        [$tokenKey, $tokenString] = TokenProvider::splitToken($fullTokenString);

        try {
            return $this->sendResponse($request, $this->processToken($tokenKey, $tokenString));
        } catch (TokenException $exception) {
            $this->logger->notice('[ACTION] [INVALID_TOKEN] ' . $exception->getMessage());
            return $this->errorResponse($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        } catch (IllegalMoveException $exception) {
            $this->logger->notice('[ACTION] [ILLEGAL_MOVE] ' . $exception->getMessage());
            return $this->errorResponse('Illegal Move: ' . $exception->getMessage(), Response::HTTP_CONFLICT);
        }
    }

    // phpcs:disable Generic.Metrics.CyclomaticComplexity.MaxExceeded
    private function processToken(string $tokenKey, string $tokenString): array
    {
        // route the tokens. also allow tokens from yesterday to cater for the date boundary
        switch ($tokenKey) {
            case TokenProvider::getActionPath(AcknowledgePromotionToken::class, $this->now):
            case TokenProvider::getActionPath(AcknowledgePromotionToken::class, $this->yesterday):
                return $this->acknowledgePromotionAction->invoke($tokenString);

            case TokenProvider::getActionPath(ShipHealthToken::class, $this->now):
            case TokenProvider::getActionPath(ShipHealthToken::class, $this->yesterday):
                return $this->addHealthAction->invoke($tokenString);

            case TokenProvider::getActionPath(UseOffenceEffectToken::class, $this->now):
            case TokenProvider::getActionPath(UseOffenceEffectToken::class, $this->yesterday):
                return $this->applyOffenceEffectAction->invoke($tokenString);

            case TokenProvider::getActionPath(JoinConvoyToken::class, $this->now):
            case TokenProvider::getActionPath(JoinConvoyToken::class, $this->yesterday):
                return $this->joinConvoyAction->invoke($tokenString);

            case TokenProvider::getActionPath(LeaveConvoyToken::class, $this->now):
            case TokenProvider::getActionPath(LeaveConvoyToken::class, $this->yesterday):
                return $this->leaveConvoyAction->invoke($tokenString);

            case TokenProvider::getActionPath(MoveShipToken::class, $this->now):
            case TokenProvider::getActionPath(MoveShipToken::class, $this->yesterday):
                return $this->moveShipAction->invoke($tokenString);

            case TokenProvider::getActionPath(PurchaseShipToken::class, $this->now):
            case TokenProvider::getActionPath(PurchaseShipToken::class, $this->yesterday):
                return $this->purchaseShipAction->invoke($tokenString);

            case TokenProvider::getActionPath(SellShipToken::class, $this->now):
            case TokenProvider::getActionPath(SellShipToken::class, $this->yesterday):
                return $this->sellShipAction->invoke($tokenString);

            case TokenProvider::getActionPath(PurchaseEffectToken::class, $this->now):
            case TokenProvider::getActionPath(PurchaseEffectToken::class, $this->yesterday):
                return $this->purchaseEffectAction->invoke($tokenString);

            case TokenProvider::getActionPath(RenameShipToken::class, $this->now):
            case TokenProvider::getActionPath(RenameShipToken::class, $this->yesterday):
                return $this->renameShipAction->invoke($tokenString);

            case TokenProvider::getActionPath(RequestShipNameToken::class, $this->now):
            case TokenProvider::getActionPath(RequestShipNameToken::class, $this->yesterday):
                return $this->requestShipNameAction->invoke($tokenString);

            case TokenProvider::getActionPath(ShipDefenceEffectToken::class, $this->now):
            case TokenProvider::getActionPath(ShipDefenceEffectToken::class, $this->yesterday):
                return $this->applyShipDefenceEffectAction->invoke($tokenString);

            case TokenProvider::getActionPath(ShipTravelEffectToken::class, $this->now):
            case TokenProvider::getActionPath(ShipTravelEffectToken::class, $this->yesterday):
                return $this->applyShipTravelEffectAction->invoke($tokenString);

            case TokenProvider::getActionPath(DropCrateToken::class, $this->now):
            case TokenProvider::getActionPath(DropCrateToken::class, $this->yesterday):
                return $this->dropCrateAction->invoke($tokenString);

            case TokenProvider::getActionPath(PickupCrateToken::class, $this->now):
            case TokenProvider::getActionPath(PickupCrateToken::class, $this->yesterday):
                return $this->pickupCrateAction->invoke($tokenString);

            case TokenProvider::getActionPath(RemoveAuthProviderToken::class, $this->now):
            case TokenProvider::getActionPath(RemoveAuthProviderToken::class, $this->yesterday):
                return $this->removeAuthProviderAction->invoke($tokenString);
        }
        throw new BadRequestHttpException('Invalid token type');
    }

    private function getTokenString(Request $request): string
    {
        if ($request->getContentType() === 'json') {
            $data = jsonDecode((string)$request->getContent());
            if (!isset($data['token'])) {
                throw new BadRequestHttpException('Must be submitted as valid JSON');
            }
            return $data['token'];
        }
        return $request->get('token');
    }

    private function sendResponse(Request $request, array $responseData): Response
    {
        if ($request->getContentType() === 'json') {
            $response = new JsonResponse($responseData);
            return $this->noCacheResponse($response);
        }
        // todo - server rendered via POST
        throw new ConflictHttpException('Not available outside of JSON yet');
    }

    private function errorResponse(string $message, int $code = Response::HTTP_INTERNAL_SERVER_ERROR): Response
    {
        $data = [
            'error' => $message,
        ];
        $response = new JsonResponse($data, $code);
        return $this->noCacheResponse($response);
    }
}
