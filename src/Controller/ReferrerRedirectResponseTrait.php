<?php
declare(strict_types=1);

namespace App\Controller;

use App\Infrastructure\ApplicationConfig;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

trait ReferrerRedirectResponseTrait
{
    private function getReferrerRedirectResponse(Request $request, ApplicationConfig $applicationConfig,  $path): Response
    {
        $hostname = $this->applicationConfig->getWebHostname();
        $referrer = $request->server->get('HTTP_REFERER');
        if ($referrer) {
            $url = parse_url($referrer);
            $path = $url['path'] ?? $path;
        }

        $response = new RedirectResponse($hostname . $path);
        return $this->noCacheResponse($response);
    }
}
