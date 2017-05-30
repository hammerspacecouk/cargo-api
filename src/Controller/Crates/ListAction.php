<?php
declare(strict_types = 1);
namespace App\Controller\Crates;

use App\Controller\PaginationRequestTrait;
use App\Service\CratesService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class ListAction
{
    use PaginationRequestTrait;

    private const PER_PAGE = 1;

    public function __invoke(
        Request $request,
        CratesService $cratesService
    ): JsonResponse {
        $page = $this->getPageNumber($request);
        $total = $cratesService->countAllAvailable();
        $pagination = $this->getPagination($request, $page, self::PER_PAGE, $total);

        return new JsonResponse([
            'pagination' => $pagination,
            'items' => $cratesService->findAvailable(self::PER_PAGE, $page),
        ]);
    }
}
