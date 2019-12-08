<?php
declare(strict_types=1);

namespace App\Controller\Play;

use App\Controller\CacheControlResponseTrait;
use App\Controller\UserAuthenticationTrait;
use App\Domain\Entity\User;
use App\Service\AuthenticationService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Route;

class MapAction
{
    use CacheControlResponseTrait;
    use UserAuthenticationTrait;

    /** @var AuthenticationService */
    protected $authenticationService;
    /** @var User */
    protected $user;
    /** @var LoggerInterface */
    protected $logger;

    public static function getRouteDefinition(): Route
    {
        return new Route('/play/map.svg', [
            '_controller' => self::class,
        ]);
    }

    public function __construct(
        AuthenticationService $authenticationService,
        LoggerInterface $logger
    ) {
        $this->authenticationService = $authenticationService;
        $this->logger = $logger;
    }

    public function __invoke(
        Request $request
    ): Response {
        $this->user = $this->getUser($request, $this->authenticationService);
        $response = new Response($this->buildSvg());
        $response->headers->set('content-type', 'image/svg+xml');
        return $this->noCacheResponse($response);
    }

    private function buildSvg(): string
    {
        // temporary
        return trim(file_get_contents(__DIR__ . '/../../../build/map.svg'));
    }
}
