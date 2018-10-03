<?php
declare(strict_types=1);

use App\Controller;
use App\Domain\ValueObject\Token\Action;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;

$collection = new RouteCollection();

// home
$collection->add('home', new Route('/', [
    '_controller' => Controller\Home\IndexAction::class,
]));
$collection->add('app_status', new Route('/status', [
    '_controller' => Controller\Home\StatusAction::class,
]));

// login
$collection->add('login', new Route('/login', [
    '_controller' => Controller\Security\LoginAction::class,
]));
$collection->add('logout', new Route('/logout', [
    '_controller' => Controller\Security\LogoutAction::class,
]));
$collection->add('login_check', new Route('/login/check', [
    '_controller' => Controller\Security\CheckLoginAction::class,
]));
$collection->add('login_email', new Route('/login/email', [
    '_controller' => Controller\Security\LoginEmailAction::class,
]));
$collection->add('login_facebook', new Route('/login/facebook', [
    '_controller' => Controller\Security\LoginFacebookAction::class,
]));
$collection->add('login_google', new Route('/login/google', [
    '_controller' => Controller\Security\LoginGoogleAction::class,
]));
$collection->add('login_microsoft', new Route('/login/microsoft', [
    '_controller' => Controller\Security\LoginMicrosoftAction::class,
]));
$collection->add('login_twitter', new Route('/login/twitter', [
    '_controller' => Controller\Security\LoginTwitterAction::class,
]));
$collection->add('profile_show', new Route('/profile', [
    '_controller' => Controller\Profile\ShowAction::class,
]));
$collection->add('profile_sessions', new Route('/profile/sessions', [
    '_controller' => Controller\Profile\SessionsAction::class,
]));
$collection->add('profile_delete', new Route('/profile/delete', [
    '_controller' => Controller\Profile\DeleteAction::class,
]));

// Crates
$collection->add('crates_list', new Route('/crates', [
    '_controller' => Controller\Crates\ListAction::class,
]));

$collection->add('crates_show', new Route('/crates/{uuid}', [
    '_controller' => Controller\Crates\ShowAction::class,
], [
    'uuid' => Uuid::VALID_PATTERN,
]));

// Ports
$collection->add('ports_list', new Route('/ports', [
    '_controller' => Controller\Ports\ListAction::class,
]));

$collection->add('ports_show', new Route('/ports/{uuid}', [
    '_controller' => Controller\Ports\ShowAction::class,
], [
    'uuid' => Uuid::VALID_PATTERN,
]));

$collection->add('ports_crates', new Route('/ports/{uuid}/crates', [
    '_controller' => Controller\Ports\CratesAction::class,
], [
    'uuid' => Uuid::VALID_PATTERN,
]));

// ships
$collection->add('ships_list', new Route('/ships', [
    '_controller' => Controller\Ships\ListAction::class,
]));

$collection->add('ships_show', new Route('/ships/{uuid}', [
    '_controller' => Controller\Ships\ShowAction::class,
], [
    'uuid' => Uuid::VALID_PATTERN,
]));

// Play - requires cookie
$collection->add('play_status', new Route('/play', [
    '_controller' => Controller\Play\IndexAction::class,
]));

$collection->add('play_positions_ship', new Route('/play/{uuid}', [
    '_controller' => Controller\Play\ShipAction::class,
], [
    'uuid' => Uuid::VALID_PATTERN,
]));

// actions
$collection->add('actions_move_ship', new Route(Action\MoveShipToken::getPath(), [
    '_controller' => Controller\Actions\MoveShipAction::class,
]));

$collection->add('actions_acknowledge_promotion', new Route(Action\AcknowledgePromotionToken::getPath(), [
    '_controller' => Controller\Actions\AcknowledgePromotionAction::class,
]));

$collection->add('actions_rename_ship', new Route(Action\RenameShipToken::getPath(), [
    '_controller' => Controller\Actions\RenameShipAction::class,
]));

$collection->add('actions_request_ship_name', new Route(Action\RequestShipNameToken::getPath(), [
    '_controller' => Controller\Actions\RequestShipNameAction::class,
]));

return $collection;
