<?php
declare(strict_types = 1);
namespace App\Controller\Ships;

use App\Controller\PaginationRequestTrait;
use App\Service\ShipsService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class ListAction
{
    use PaginationRequestTrait;

    private const PER_PAGE = 50;

    public function __invoke(
        Request $request,
        ShipsService $shipsService,
        LoggerInterface $logger
    ): JsonResponse {

        $logger->debug(__CLASS__);
        $page = $this->getPageNumber($request);
        $total = $shipsService->countAll();
        $pagination = $this->getPagination($request, $page, self::PER_PAGE, $total);

        $items = [];
        if ($total) {
            $items = $shipsService->findAll(self::PER_PAGE, $page);
        }

        return new JsonResponse([
            'pagination' => $pagination,
            'items' => $items,
        ]);
    }
}
