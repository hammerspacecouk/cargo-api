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

    // login
    Controller\Security\LoginAction::class,
    Controller\Security\LogoutAction::class,
    Controller\Security\CheckLoginAction::class,
    Controller\Security\LoginAnonymousAction::class,
    Controller\Security\LoginEmailAction::class,
    Controller\Security\LoginFacebookAction::class,
    Controller\Security\LoginGoogleAction::class,
    Controller\Security\LoginMicrosoftAction::class,
    Controller\Security\LoginTwitterAction::class,

    // ports
    Controller\Ports\ListAction::class,
    Controller\Ports\ShowAction::class,

    // ships
    Controller\Ships\ListAction::class,
    Controller\Ships\ShowAction::class,

    // profile (requires cookie)
    Controller\Profile\ShowAction::class,
    Controller\Profile\SessionsAction::class,
    Controller\Profile\DeleteAction::class,

    // play (requires cookie)
    Controller\Play\IndexAction::class,
    Controller\Play\UpgradesAction::class,
    Controller\Play\ShipAction::class,


    // actions (uses token)
    Controller\Actions\AcknowledgePromotionAction::class,
    Controller\Actions\AddHealthAction::class,
    Controller\Actions\ApplyOffenceEffectAction::class,
    Controller\Actions\MoveShipAction::class,
    Controller\Actions\PurchaseShipAction::class,
    Controller\Actions\PurchaseEffectAction::class,
    Controller\Actions\RenameShipAction::class,
    Controller\Actions\RequestShipNameAction::class,
    Controller\Actions\Effects\ApplyShipDefenceEffectAction::class,
    Controller\Actions\Effects\ApplyShipTravelEffectAction::class,
    Controller\Actions\PortActions\DropCrateAction::class,
    Controller\Actions\PortActions\PickupCrateAction::class,
];

foreach ($actions as $action) {
    foreach ($action::getRouteDefinition() as $name => $definition) {
        $collection->add($name, $definition);
    }
}

return $collection;
