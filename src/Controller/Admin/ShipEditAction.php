<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Domain\Entity\Ship;
use App\Service\AuthenticationService;
use App\Service\ShipsService;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Route;

class ShipEditAction extends AbstractAdminAction
{
    private ShipsService $shipsService;
    private LoggerInterface $logger;

    public static function getRouteDefinition(): Route
    {
        return new Route('/admin/ships/{shipId}', [
            '_controller' => self::class,
        ]);
    }

    public function __construct(
        AuthenticationService $authenticationService,
        LoggerInterface $logger,
        ShipsService $shipsService
    ) {
        parent::__construct($authenticationService);
        $this->shipsService = $shipsService;
        $this->logger = $logger;
    }

    public function invoke(Request $request): Response
    {
        /** @var Ship|null $ship */
        $ship = $this->shipsService->getById(Uuid::fromString($request->get('shipId')));
        if (!$ship) {
            throw new NotFoundHttpException('No such user');
        }

        if ($request->isMethod('POST')) {
            $ship = $this->handlePost($ship, $request);
        }

        $body = <<<BODY
        <!DOCTYPE html>
        <html>
        <head>
            <title>Edit Ship {$ship->getName()}</title>
        </head>
        <body>
            <h1>{$ship->getName()}</h1>
            <form method="post">
                <table>
                <tbody>
                    <tr>
                        <th>Name</th>
                        <td><input name="name" value="{$ship->getName()}" /></td>
                    </tr>
                    <tr>
                        <th>Strength</th>
                        <td><input name="strength" type="number" value="{$ship->getStrength()}" /></td>
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

    private function handlePost(Ship $ship, Request $request): Ship
    {
        $before = $ship->jsonSerialize();

        // do updates
        $this->shipsService->quickEditShip($ship->getId(), [
            'name' => $request->get('name'),
            'strength' => (int)$request->get('strength'),
        ]);


        // re-fetch ship now that it has been updated
        $after = $this->shipsService->getById($ship->getId());
        if (!$after) {
            throw new \LogicException('Does not exist! Eh?');
        }

        $this->logger->warning('[ADMIN] [SHIP_EDIT]', [
            'before' => $before,
            'after' => $after->jsonSerialize(),
        ]);

        return $after;
    }
}
