<?php
declare(strict_types = 1);
namespace App\Controller\Ports\Traits;

use App\Controller\IDRequestTrait;
use App\Domain\Entity\Port;
use App\Service\PortsService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

trait GetPortTrait
{
    use IDRequestTrait;

    public function getPort(
        Request $request,
        PortsService $portsService
    ): Port {
        $uuid = $this->getID($request);
        $port = $portsService->findByID($uuid);
        if (!$port) {
            throw new NotFoundHttpException('No such port');
        }
        return $port;
    }
}
