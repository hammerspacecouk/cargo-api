<?php
declare(strict_types=1);

namespace App\Controller\Security;

use App\Infrastructure\ApplicationConfig;
use App\Data\FlashDataStore;
use App\Data\TokenHandler;
use App\Domain\Exception\TokenException;
use App\Domain\ValueObject\Message\Info;
use App\Service\AuthenticationService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class LogoutAction
{
    public function __invoke(
        Request $request,
        FlashDataStore $flashData,
        AuthenticationService $authenticationService,
        TokenHandler $tokenHandler,
        ApplicationConfig $applicationConfig
    ): Response {
        try {
            // todo - remove the authentication token
            $authenticationService->removeFromRequest($request);

        } catch (TokenException $e) {
            // if the token was invalid or expired, then just carry on clearing the session
        }

        // destroy all flash cookies
        $flashData->destroy();

        // set an ok message
        $flashData->addMessage(new Info('Logged out'));

        $response = new RedirectResponse($applicationConfig->getWebHostname());

        // remove previous refresh and access cookies
        $response = $tokenHandler->clearCookiesFromResponse($response);

        // redirect to the application homepage, now that you're logged out
        return $response;
    }
}
