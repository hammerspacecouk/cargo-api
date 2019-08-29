<?php
declare(strict_types=1);

namespace App\Controller\Profile;

use App\Controller\AbstractUserAction;
use App\Response\ProfileResponse;
use App\Service\AuthenticationService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

class ShowAction extends AbstractUserAction
{
    private $profileResponse;

    public static function getRouteDefinition(): Route
    {
        return new Route('/profile', [
            '_controller' => self::class,
        ]);
    }

    public function __construct(

        AuthenticationService $authenticationService,
        ProfileResponse $profileResponse,
        LoggerInterface $logger
    ) {
        parent::__construct($authenticationService, $logger);
        $this->profileResponse = $profileResponse;
    }

    public function invoke(
        Request $request
    ): array {
        return $this->profileResponse->getResponseDataForUser($this->user);
    }
}
