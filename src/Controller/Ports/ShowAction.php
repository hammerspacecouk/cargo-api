<?php
declare(strict_types = 1);
namespace App\Controller\Ports;

use App\Service\PortsService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class ShowAction
{
    use Traits\GetPortTrait;

    public function __invoke(
        Request $request,
        PortsService $portsService,
        LoggerInterface $logger
    ): JsonResponse {

        $logger->info(__CLASS__);
        $port = $this->getPort($request, $portsService);
        return new JsonResponse($port);
    }
}
