<?php
declare(strict_types=1);

namespace App\Controller\Play\Ships;

use App\Controller\PaginationRequestTrait;
use App\Controller\Security\Traits\UserTokenTrait;
use App\Service\ShipsService;
use App\Service\TokensService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * The 'My' Section reads from your cookie, so is custom and un-cacheable
 */
class ListAction
{
    use UserTokenTrait;
    use PaginationRequestTrait;

    const PER_PAGE = 100;

    public function __invoke(
        Request $request,
        TokensService $tokensService,
        ShipsService $shipsService,
        LoggerInterface $logger
    ): JsonResponse {

        $logger->debug(__CLASS__);
        $userId = $this->getUserId($request, $tokensService);

        $page = $this->getPageNumber($request);
        $total = $shipsService->countForOwnerIDWithLocation($userId);
        $pagination = $this->getPagination($request, $page, self::PER_PAGE, $total);

        $items = [];
        if ($total) {
            $items = $shipsService->getForOwnerIDWithLocation($userId, self::PER_PAGE, $page);
        }

        return $this->userResponse(new JsonResponse([
            'pagination' => $pagination,
            'items' => $items,
        ]));
    }
}
