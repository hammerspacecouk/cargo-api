<?php
declare(strict_types=1);

namespace App\Controller\Profile;

use App\Controller\CacheControlResponseTrait;
use App\Controller\UserAuthenticationTrait;
use App\Domain\Exception\InvalidTokenException;
use App\Domain\ValueObject\Token\DeleteAccountToken;
use App\Infrastructure\ApplicationConfig;
use App\Service\AuthenticationService;
use App\Service\UsersService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\Routing\Route;
use function PHPUnit\Framework\stringContains;

class SetNicknameAction
{
    use CacheControlResponseTrait;
    use UserAuthenticationTrait;

    private AuthenticationService $authenticationService;
    private ApplicationConfig $applicationConfig;
    private LoggerInterface $logger;
    private UsersService $usersService;

    public static function getRouteDefinition(): Route
    {
        return new Route('/profile/set-nickname', [
            '_controller' => self::class,
        ], [], [], '', [], ['POST']);
    }

    public function __construct(
        AuthenticationService $authenticationService,
        ApplicationConfig $applicationConfig,
        UsersService $usersService,
        LoggerInterface $logger
    ) {
        $this->authenticationService = $authenticationService;
        $this->applicationConfig = $applicationConfig;
        $this->logger = $logger;
        $this->usersService = $usersService;
    }

    public function __invoke(
        Request $request
    ): Response {

        $token = $request->get('token');
        $nickname = $request->get('nickname');
        if (empty($token) ||
            empty($nickname) ||
            strlen($nickname) > 50 ||
            str_contains(strtolower($nickname), 'admin')
        ) {
            throw new BadRequestHttpException('Bad Token or Nickname Parameter');
        }

        $user = $this->getUser($request, $this->authenticationService);
        try {
            $this->usersService->setNickname($user, $token, $nickname);
        } catch (InvalidTokenException $exception) {
            throw new BadRequestException('Bad token');
        }

        $response = new RedirectResponse(
            $this->applicationConfig->getWebHostname() .
            '/play/profile'
        );
        return $this->noCacheResponse($response);
    }
}
