<?php
declare(strict_types=1);

namespace App\Controller\Actions;

use App\Response\ProfileResponse;
use App\Service\AuthenticationService;
use App\Service\UsersService;

class RemoveAuthProviderAction
{
    private AuthenticationService $authenticationService;
    private ProfileResponse $profileResponse;
    private UsersService $usersService;

    public function __construct(
        AuthenticationService $authenticationService,
        ProfileResponse $profileResponse,
        UsersService $usersService
    ) {
        $this->authenticationService = $authenticationService;
        $this->profileResponse = $profileResponse;
        $this->usersService = $usersService;
    }

    // general status and stats of the game as a whole
    public function invoke(string $tokenString): array
    {
        $token = $this->authenticationService->parseRemoveAuthProviderToken($tokenString);
        $this->authenticationService->useRemoveAuthProviderToken($token);

        // fetch the updated profile data
        $user = $this->usersService->getByID($token->getUserId());
        if (!$user) {
            throw new \RuntimeException('Something went very wrong here');
        }
        return $this->profileResponse->getResponseDataForUser($user);
    }
}
