<?php
declare(strict_types=1);

namespace App\Controller\Actions;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

class AbstractAction
{
    protected function getTokenDataFromRequest(Request $request): string
    {
        return $this->getDataFromRequest($request, 'token');
    }

    private function getDataFromRequest(Request $request, string $dataKey)
    {
        if ($request->getMethod() !== 'POST') {
            throw new MethodNotAllowedHttpException(['POST']);
        }
        if ($request->getContentType() !== 'json') {
            throw new BadRequestHttpException('Must be submitted as JSON');
        }
        $data = json_decode($request->getContent(), true);
        if (!$data) {
            throw new BadRequestHttpException('Must be submitted as valid JSON');
        }

        if (isset($data[$dataKey])) {
            return $data[$dataKey];
        }

        throw new BadRequestHttpException('Bad data supplied. Could not find ' . $dataKey);
    }

    protected function actionResponse(JsonResponse $response): JsonResponse
    {
        $response->headers->set('cache-control', 'no-cache, no-store, must-revalidate');
        return $response;
    }
}
