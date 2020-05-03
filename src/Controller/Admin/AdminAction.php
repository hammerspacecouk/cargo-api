<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Infrastructure\DateTimeFactory;
use App\Service\AuthenticationService;
use App\Service\StatsService;
use DateInterval;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Route;

class AdminAction extends AbstractAdminAction
{
    private StatsService $statsService;

    public static function getRouteDefinition(): Route
    {
        return new Route('/admin', [
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
        $userStats = $this->getUserStats();
        $dailyRegistrations = $this->getRegistrationsPerDay();
        $authProviders = $this->getAuthProviders();
        $ranks = $this->getRanks();

        $body = <<<BODY
        <!DOCTYPE html>
        <html>
        <head>
            <title>Admin</title>
            <style>
                body {
                    font-family: sans-serif;
                    font-weight: lighter;
                    margin: 32px auto;
                    max-width: 768px;
                }
                table {
                    width: 100%;
                }
                th, td {
                    text-align: left;
                    padding: 8px;
                }
                thead {
                    background: #555;
                    color: #fff;
                }
                tr:nth-child(2n) {
                    background: #dedede;
                }
            </style>
        </head>
        <body>
            <h1>Admin</h1>
            $userStats
            $dailyRegistrations
            $authProviders
            $ranks
        </body>
        </html>
        BODY;

        return new Response($body);
    }

    private function getAuthProviders(): string
    {
        $rows = '';
        foreach ($this->statsService->countAuthProviders() as $rank => $count) {
            $rows .= <<<ROW
            <tr>
                <td>$rank</td>
                <td>$count</td>
            </tr>
            ROW;
        }

        return <<<BODY
            <h2>Auth Providers</h2>
            <table>
            <thead>
                <tr>
                <th>Provider</th>
                <th>Count</th>
                </tr>
            </thead>
            <tbody>
            $rows
            </tbody>
            </table>
        BODY;
    }

    private function getRanks(): string
    {
        $rows = '';
        foreach ($this->statsService->countRanks() as $result) {
            $rows .= <<<ROW
            <tr>
                <td>{$result['name']}</td>
                <td>{$result['count']}</td>
            </tr>
            ROW;
        }

        return <<<BODY
            <h2>Ranks</h2>
            <table>
            <thead>
                <tr>
                <th>Rank</th>
                <th>Count</th>
                </tr>
            </thead>
            <tbody>
            $rows
            </tbody>
            </table>
        BODY;
    }

    private function getRegistrationsPerDay(): string
    {
        $now = DateTimeFactory::now();
        $start = $now->sub(new DateInterval('P7D'));

        $rows = '';
        foreach ($this->statsService->registrationsPerDay($start, $now) as $day => $count) {
            $rows .= <<<ROW
            <tr>
                <td>$day</td>
                <td>$count</td>
            </tr>
            ROW;
        }
        return <<<BODY
        <h2>Registrations</h2>
        <table>
        <thead>
            <tr>
            <th>Date</th>
            <th>Count</th>
            </tr>
        </thead>
        <tbody>
        $rows
        </tbody>
        </table>
        <form action="/admin/registrations">
            <fieldset>
            <legend>CSV</legend>
            <input type="date" name="since" required />
            <input type="date" name="until" required />
            <button type="submit">Download</button>
            </fieldset>
        </form>
        BODY;
    }

    private function getUserStats(): string
    {
        $totalUsers = $this->statsService->countAllUsers();
        $activeUsers = $this->statsService->countActiveUsers();

        return <<<BODY
        <h2>Users</h2>
        <table>
        <tbody>
        <tr>
            <th>Total users:</th>
            <td>$totalUsers</td>
        </tr>
        <tr>
            <th>Active users:</th>
            <td>$activeUsers</td>
        </tr>
        </tbody>
        </table>
        BODY;
    }
}
