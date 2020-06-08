<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\AuthenticationService;
use App\Service\ConfigService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Route;
use function App\Functions\Json\jsonDecode;

class ConfigAction extends AbstractAdminAction
{
    private ConfigService $configService;

    public static function getRouteDefinition(): Route
    {
        return new Route('/admin/config', [
            '_controller' => self::class,
        ]);
    }

    public function __construct(
        AuthenticationService $authenticationService,
        ConfigService $configService
    ) {
        parent::__construct($authenticationService);
        $this->configService = $configService;
    }

    public function invoke(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $data = jsonDecode($request->get('config'));
            $this->configService->setConfig($data);
        }

        $config = \json_encode($this->configService->getConfig(), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);

        $body = <<<BODY
        <!DOCTYPE html>
        <html>
        <head>
            <title>Config</title>
        </head>
        <body>
            <h1>Config</h1>
            <form method="post">
                <textarea rows="50" cols="100" name="config">$config</textarea>
                <div><button type="submit">Save</button></div>
            </form>
        </body>
        </html>
        BODY;


        return new Response($body);
    }
}
