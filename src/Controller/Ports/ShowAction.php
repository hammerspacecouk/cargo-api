<?php
declare(strict_types=1);

namespace App\Controller\Ports;

use App\Service\PortsService;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Validator\GenericValidator;
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
            'uuid' => '^[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}$', // TODO - Symfony 5.2 // (new GenericValidator())->getPattern(),
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
