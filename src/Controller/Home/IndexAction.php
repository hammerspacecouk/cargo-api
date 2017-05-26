<?php
declare(strict_types = 1);
namespace App\Controller\Home;

use App\Hello;
use Symfony\Component\HttpFoundation\JsonResponse;

class IndexAction
{
    public function __invoke(Hello $hello): JsonResponse
    {
        return new JsonResponse(['message' => $hello->getInput()]);
    }
}
