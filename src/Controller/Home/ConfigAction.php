<?php
declare(strict_types=1);

namespace App\Controller\Home;

use App\Controller\UserAuthenticationTrait;
use function App\Functions\DateTimes\jsonDecode;
use App\Service\AuthenticationService;
use App\Service\ConfigService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Route;

class ConfigAction
{
    use UserAuthenticationTrait;

    private $configService;
    private $authenticationService;

    public static function getRouteDefinition(): array
    {
        return [
            self::class => new Route('/config', [
                '_controller' => self::class,
            ]),
        ];
    }

    public function __construct(
        AuthenticationService $authenticationService,
        ConfigService $configService
    ) {
        $this->configService = $configService;
        $this->authenticationService = $authenticationService;
    }

    // health status of the application itself
    public function __invoke(Request $request): Response
    {
        $user = $this->getUser($request, $this->authenticationService);

        if (!$user->isAdmin()) {
            // Don't tell the world this page exists. Oh wait; it's in a public git repo!
            throw new NotFoundHttpException('Not Found');
        }

        if ($request->isMethod('POST')) {
            $data = jsonDecode($request->get('config'));
            $this->configService->setConfig($data);
        }

        // if POST, update it

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
