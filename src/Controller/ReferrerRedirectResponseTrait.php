<?php
declare(strict_types=1);

namespace App\Controller;

use App\Infrastructure\ApplicationConfig;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

trait ReferrerRedirectResponseTrait
{
    private function getReferrerRedirectResponse(
        Request $request,
        ApplicationConfig $applicationConfig,
        string $path
    ): Response {
        $hostname = $applicationConfig->getWebHostname();
        $returnPath = $request->get('returnPath');
        if (!empty($returnPath)) {
            $path = $returnPath;
        }
        $response = new RedirectResponse($hostname . $path);
        return $this->noCacheResponse($response);
    }
}
