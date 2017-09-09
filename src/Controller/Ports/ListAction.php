<?php
declare(strict_types=1);

namespace App\Controller\Ports;

use App\Controller\PaginationRequestTrait;
use App\Service\PortsService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class ListAction
{
    use PaginationRequestTrait;

    private const PER_PAGE = 50;

    public function __invoke(
        Request $request,
        PortsService $portsService,
        LoggerInterface $logger
    ): JsonResponse {

        $logger->debug(__CLASS__);
        $page = $this->getPageNumber($request);
        $total = $portsService->countAll();
        $pagination = $this->getPagination($request, $page, self::PER_PAGE, $total);

        $items = [];
        if ($total) {
            $items = $portsService->findAll(self::PER_PAGE, $page);
        }

        return new JsonResponse([
            'pagination' => $pagination,
            'items' => $items,
        ]);
    }
}
