<?php
declare(strict_types=1);

namespace App\Controller\Security;

use App\Config\ApplicationConfig;
use App\Data\FlashDataStore;
use App\Data\TokenHandler;
use App\Domain\ValueObject\Message\Info;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class LogoutAction
{
    public function __invoke(
        Request $request,
        FlashDataStore $flashData,
        TokenHandler $tokenHandler,
        ApplicationConfig $applicationConfig
    ): RedirectResponse {
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
