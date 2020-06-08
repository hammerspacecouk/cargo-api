<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Domain\Entity\User;
use App\Service\AuthenticationService;
use App\Service\UsersService;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Route;

class PlayerEditAction extends AbstractAdminAction
{
    private UsersService $usersService;
    private LoggerInterface $logger;

    public static function getRouteDefinition(): Route
    {
        return new Route('/admin/players/{playerId}', [
            '_controller' => self::class,
        ]);
    }

    public function __construct(
        AuthenticationService $authenticationService,
        LoggerInterface $logger,
        UsersService $usersService
    ) {
        parent::__construct($authenticationService);
        $this->usersService = $usersService;
        $this->logger = $logger;
    }

    public function invoke(Request $request): Response
    {
        /** @var User|null $user */
        $user = $this->usersService->getById(Uuid::fromString($request->get('playerId')));
        if (!$user) {
            throw new NotFoundHttpException('No such user');
        }

        if ($request->isMethod('POST')) {
            $user = $this->handlePost($user, $request);
        }

        $nickname = $user->hasCustomNickname() ? $user->getDisplayName() : '';
        $body = <<<BODY
        <!DOCTYPE html>
        <html>
        <head>
            <title>Edit Player {$user->getDisplayName()}</title>
        </head>
        <body>
            <h1>{$user->getDisplayName()}</h1>
            <form method="post">
                <table>
                <tbody>
                    <tr>
                        <th>Display name</th>
                        <td><input name="nickname" value="{$nickname}" /></td>
                    </tr>
                    <tr>
                        <th>Permission level</th>
                        <td><input name="permissionLevel" type="number" value="{$user->getPermissionLevel()}" /></td>
                    </tr>
                </tbody>
                </table>
                <div><button type="submit">Save</button></div>
            </form>
        </body>
        </html>
        BODY;


        return new Response($body);
    }

    private function handlePost(User $user, Request $request): User
    {
        $before = $user->jsonSerialize();

        // do updates
        $this->usersService->quickEditUser($user->getId(), [
            'nickname' => $request->get('nickname'),
            'permissionLevel' => (int)$request->get('permissionLevel'),
        ]);

        // re-fetch user now that it has been updated
        $after = $this->usersService->getById($user->getId());
        if (!$after) {
            throw new \LogicException('Does not exist! Eh?');
        }

        $this->logger->warning('[ADMIN] [USER_EDIT]', [
            'before' => $before,
            'after' => $after->jsonSerialize(),
        ]);

        return $after;
    }
}
