<?php
declare(strict_types=1);

namespace App\Controller\Security;

use App\Service\Oauth\OAuthServiceInterface;
use League\OAuth2\Client\Provider\AbstractProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractOauthLoginAction
{
    private AbstractProvider $oAuthProvider;
    private OAuthHandler $oAuthHandler;
    private OAuthServiceInterface $oAuthService;

    public function __construct(
        AbstractProvider $oAuthProvider,
        OAuthHandler $oAuthHandler,
        OAuthServiceInterface $oAuthService
    ) {
        $this->oAuthProvider = $oAuthProvider;
        $this->oAuthHandler = $oAuthHandler;
        $this->oAuthService = $oAuthService;
    }

    public function __invoke(
        Request $request
    ): Response {
        return $this->oAuthHandler->__invoke($request, $this->oAuthProvider, $this->oAuthService);
    }
}
