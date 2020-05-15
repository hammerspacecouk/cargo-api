<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\AuthenticationService;
use App\Service\StatsService;
use DateTimeImmutable;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Route;

class RegistrationsAction extends AbstractAdminAction
{
    private StatsService $statsService;

    public static function getRouteDefinition(): Route
    {
        return new Route('/admin/registrations', [
            '_controller' => self::class,
        ]);
    }

    public function __construct(
        AuthenticationService $authenticationService,
        StatsService $statsService
    ) {
        parent::__construct($authenticationService);
        $this->statsService = $statsService;
    }

    public function invoke(Request $request): Response
    {
        $since = new DateTimeImmutable($request->get('since') . 'T12:00:00Z');
        $until = new DateTimeImmutable($request->get('until') . 'T23:59:59Z');

        $results = $this->statsService->registrationsPerDay($since, $until);

        $csv = "Date,Count\n";
        foreach ($results as $key => $value) {
            $csv .= $key . ',' . $value . "\n";
        }

        $filename = $request->get('since') . '-' . $request->get('until') . '-registrations.csv';

        $response = new Response($csv);
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $response;
    }
}
