<?php
declare(strict_types=1);

namespace App\Controller\Ports;

use App\Service\PortsService;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

class ShowAction
{
    use Traits\GetPortTrait;

    public static function getRouteDefinition(): Route
    {
        return new Route('/ports/{uuid}', [
            '_controller' => self::class,
        ], [
            'uuid' => Uuid::VALID_PATTERN,
        ]);
    }

    public function __invoke(
        Request $request,
        PortsService $portsService,
        LoggerInterface $logger
    ): JsonResponse {

        $logger->debug(__CLASS__);
        $port = $this->getPort($request, $portsService);
        $r = new JsonResponse($port);

        // todo - abstract PublicResponse
        $r->setMaxAge(600);
        $r->setPublic();
        return $r;
    }
}
