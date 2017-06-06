<?php
declare(strict_types = 1);
namespace App\Controller\Home;

use Symfony\Component\HttpFoundation\JsonResponse;

class IndexAction
{
    public function __invoke(): JsonResponse
    {
        return new JsonResponse('Nothing here... yet');
    }
}
