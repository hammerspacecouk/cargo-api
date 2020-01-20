<?php
declare(strict_types=1);

namespace App\Controller\Play;

use App\Controller\AbstractUserAction;
use App\Response\UpgradesResponse;
use App\Service\AuthenticationService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

class UpgradesAction extends AbstractUserAction
{
    private $upgradesResponse;

    public static function getRouteDefinition(): Route
    {
        return new Route('/play/upgrades', [
            '_controller' => self::class,
        ]);
    }

    public function __construct(
        AuthenticationService $authenticationService,
        UpgradesResponse $upgradesResponse,
        LoggerInterface $logger
    ) {
        parent::__construct($authenticationService, $logger);
        $this->upgradesResponse = $upgradesResponse;
    }

    public function invoke(
        Request $request
    ): array {
        return $this->upgradesResponse->getResponseDataForUser($this->user);
    }
}
