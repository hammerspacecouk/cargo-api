<?php
declare(strict_types = 1);
namespace App\Controller\Crates;

use App\Controller\IDRequestTrait;
use App\Service\CratesService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ShowAction
{
    use IDRequestTrait;

    public function __invoke(
        Request $request,
        CratesService $cratesService
    ): JsonResponse {
        $uuid = $this->getID($request);

        $crate = $cratesService->getByIDWithLocation($uuid);
        if (!$crate) {
            throw new NotFoundHttpException('No such crate');
        }

        return new JsonResponse($crate);
    }
}
