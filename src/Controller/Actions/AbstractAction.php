<?php
declare(strict_types = 1);
namespace App\Controller\Actions;

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

    private function getDataFromRequest(Request $request, string $dataKey)
    {
        $data = $request->get($dataKey);
        if (!$data) {
            throw new BadRequestHttpException('Expected data not found: ' . $dataKey);
        }
        return $data;
    }
}