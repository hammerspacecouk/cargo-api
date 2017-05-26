<?php
declare(strict_types = 1);
namespace App\Controller\Home;

use App\Hello;
use Symfony\Component\HttpFoundation\JsonResponse;

class NameAction
{
    public function __invoke(string $name, Hello $hello): JsonResponse
    {
        return new JsonResponse([
            'message' => $hello->getInput(),
            'name' => $name,
        ]);
    }
}
