<?php
declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;

trait CacheControlResponseTrait
{
    private function noCacheResponse(Response $response): Response
    {
        $response->headers->set('cache-control', 'no-cache, no-store, must-revalidate');
        return $response;
    }
}
