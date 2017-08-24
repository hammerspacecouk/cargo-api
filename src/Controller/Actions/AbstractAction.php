<?php
declare(strict_types = 1);
namespace App\Controller\Actions;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class AbstractAction
{
    protected function getTokenDataFromRequest(Request $request): string
    {
        return $this->getDataFromRequest($request, 'token');
    }

    protected function getAdditionalDataFromRequest(Request $request): array
    {
        return []; // todo - will this be used?
    }

    protected function actionResponse(JsonResponse $response): JsonResponse
    {
        $response->headers->set('cache-control', 'no-cache, no-store, must-revalidate');
        return $response;
    }

    private function getDataFromRequest(Request $request, string $dataKey)
    {
        $data = $request->get($dataKey);
        if (!$data) {
            throw new BadRequestHttpException('Expected data not found: ' . $dataKey);
        }
        return $data;
    }
}
