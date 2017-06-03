<?php
declare(strict_types = 1);
namespace App\Controller\Ports;

use App\Controller\PaginationRequestTrait;
use App\Service\CratesService;
use App\Service\PortsService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class CratesAction
{
    use PaginationRequestTrait;
    use Traits\GetPortTrait;

    private const PER_PAGE = 50;

    public function __invoke(
        Request $request,
        PortsService $portsService,
        CratesService $cratesService
    ): JsonResponse {
        $port = $this->getPort($request, $portsService);

        $page = $this->getPageNumber($request);
        $total = $cratesService->countForPort($port);
        $pagination = $this->getPagination($request, $page, self::PER_PAGE, $total);

        $items = [];
        if ($total) {
            $items = $cratesService->findActiveForPort($port, self::PER_PAGE, $page);
        }

        return new JsonResponse([
            'pagination' => $pagination,
            'items' => $items,
            'context' => $port,
        ]);
    }
}
