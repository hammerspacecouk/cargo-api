<?php
declare(strict_types=1);

namespace App\Controller;

use App\Domain\ValueObject\Pagination;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

trait PaginationRequestTrait
{
    public function getPageNumber(Request $request): int
    {
        $page = $request->get('page', 1);

        // must be an integer string
        if (strval(intval($page)) !== strval($page) || $page < 1) {
            throw new BadRequestHttpException('Invalid page value');
        }
        return (int)$page;
    }

    public function getPagination(
        Request $request,
        int $page,
        int $perPage,
        int $total
    ): Pagination {
        $pagination = new Pagination($page, $perPage, $total, $request->getPathInfo());
        if ($pagination->isOutOfBounds()) {
            throw new BadRequestHttpException('Invalid page value');
        }
        return $pagination;
    }
}
