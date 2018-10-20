<?php
declare(strict_types=1);

namespace App\Controller\Actions\PortActions;

use App\Controller\Actions\AbstractAction;
use App\Domain\Exception\IllegalMoveException;
use App\Domain\Exception\TokenException;
use App\Domain\ValueObject\Token\Action\MoveCrate\AbstractMoveCrateToken;
use App\Response\ShipInPortResponse;
use App\Service\CratesService;
use App\Service\ShipsService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

abstract class AbstractPortAction extends AbstractAction
{
    protected $cratesService;

    private $shipInPortResponse;
    private $shipsService;

    public function __construct(
        CratesService $cratesService,
        ShipsService $shipsService,
        ShipInPortResponse $shipInPortResponse,
        LoggerInterface $logger
    ) {
        parent::__construct($logger);
        $this->cratesService = $cratesService;
        $this->shipInPortResponse = $shipInPortResponse;
        $this->shipsService = $shipsService;
    }

    public function invoke(string $tokenString): array
    {
        // todo - handle GONE
        $token = $this->parseToken($tokenString);
        $this->useToken($token);

        $shipWithLocation = $this->shipsService->getByIDWithLocation($token->getShipId());
        if (!$shipWithLocation) {
            throw new BadRequestHttpException('Ship does not exist. Odd!?');
        }

        return $this->shipInPortResponse->getResponseData(
            $shipWithLocation->getOwner(),
            $shipWithLocation,
            $shipWithLocation->getLocation()
        );
    }

    abstract protected function parseToken(string $tokenString): AbstractMoveCrateToken;

    abstract protected function useToken(AbstractMoveCrateToken $token): void;
}
