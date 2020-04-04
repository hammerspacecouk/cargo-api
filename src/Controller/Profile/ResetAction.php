<?php
declare(strict_types=1);

namespace App\Controller\Profile;

use App\Controller\CacheControlResponseTrait;
use App\Controller\UserAuthenticationTrait;
use App\Infrastructure\ApplicationConfig;
use App\Service\AuthenticationService;
use App\Service\UsersService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\Routing\Route;

class ResetAction
{
    use CacheControlResponseTrait;
    use UserAuthenticationTrait;

    private $authenticationService;
    private $applicationConfig;
    private $usersService;

    public static function getRouteDefinition(): Route
    {
        return new Route('/profile/reset', [
            '_controller' => self::class,
        ]);
    }

    public function __construct(
        AuthenticationService $authenticationService,
        ApplicationConfig $applicationConfig,
        UsersService $usersService
    ) {
        $this->authenticationService = $authenticationService;
        $this->applicationConfig = $applicationConfig;
        $this->usersService = $usersService;
    }

    public function __invoke(
        Request $request
    ): Response {
        if ($request->getMethod() !== 'POST') {
            throw new MethodNotAllowedHttpException(['POST']);
        }

        $user = $this->getUser($request, $this->authenticationService);

        $token = $request->get('token');
        if ($token === null || empty($token)) {
            $resetToken = (string)$this->usersService->getResetToken($user);
            if ($resetToken) {
                $params = [
                    'token' => $resetToken,
                ];
            } else {
                $params = [
                    'ineligible' => 1,
                ];
            }

            $response = new RedirectResponse(
                $this->applicationConfig->getWebHostname() .
                '/reset?' .
                http_build_query($params),
            );
            return $this->noCacheResponse($response);
        }

        $userToReset = $this->usersService->parseResetToken($token);

        if (!$user->getId()->equals($userToReset)) {
            throw new BadRequestHttpException('Token not valid for this user');
        }

        $this->usersService->resetUser($user);

        // redirect to intro
        $response = new RedirectResponse($this->applicationConfig->getWebHostname() . '/play/intro');
        return $this->noCacheResponse($response);
    }
}
