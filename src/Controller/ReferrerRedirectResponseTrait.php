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
        $referrer = $request->server->get('HTTP_REFERER');
        if ($referrer) {
            $url = parse_url($referrer);
            if (is_array($url) && array_key_exists('path', $url)) {
                $path = $url['path'];
            }
        }

        $response = new RedirectResponse($hostname . $path);
        return $this->noCacheResponse($response);
    }
}
