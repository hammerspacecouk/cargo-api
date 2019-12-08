<?php
declare(strict_types=1);

use App\Controller;
use Symfony\Component\Routing\RouteCollection;

$collection = new RouteCollection();

// All the actions in order from least specific to most specific
$actions = [
    // home
    Controller\Home\IndexAction::class,
    Controller\Home\EmblemAction::class,
    Controller\Home\StatusAction::class,
    Controller\Home\ShipClassImageAction::class,

    // admin
    Controller\Admin\AdminAction::class,
    Controller\Admin\ConfigAction::class,
    Controller\Admin\RegistrationsAction::class,

    // login
    Controller\Security\LoginAction::class,
    Controller\Security\LogoutAction::class,
    Controller\Security\LoginAnonymousAction::class,
    Controller\Security\LoginGoogleAction::class,
    Controller\Security\LoginMicrosoftAction::class,
    Controller\Security\LoginRedditAction::class,

    // ports
    Controller\Ports\ListAction::class,
    Controller\Ports\ShowAction::class,

    // ships
    Controller\Ships\ListAction::class,
    Controller\Ships\ShowAction::class,

    // profile (requires cookie)
    Controller\Profile\ShowAction::class,
    Controller\Profile\DeleteAction::class,

    // play (requires cookie)
    Controller\Play\IndexAction::class,
    Controller\Play\UpgradesAction::class,
    Controller\Play\MapAction::class,
    Controller\Play\ShipAction::class,

    // use token
    Controller\TokenAction\TokenAction::class,
];

foreach ($actions as $action) {
    $collection->add($action, $action::getRouteDefinition());
}

return $collection;
