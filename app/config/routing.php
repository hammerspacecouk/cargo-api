<?php
declare(strict_types=1);
use App\Controller;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;

$collection = new RouteCollection();

// home
$collection->add('home', new Route('/', [
    '_controller' => Controller\Home\IndexAction::class,
]));

// login
//$collection->add('login', new Route('/login', [
//    '_controller' => Controller\Security\LoginAction::class,
//]));
$collection->add('login_google', new Route('/login/google', [
    '_controller' => Controller\Security\LoginGoogleAction::class,
]));
$collection->add('login_check', new Route('/login/check', [
    '_controller' => Controller\Security\CheckLoginAction::class,
]));

// Crates
$collection->add('crates_list', new Route('/crates', [
    '_controller' => Controller\Crates\ListAction::class,
]));

$collection->add('crates_show', new Route('/crates/{uuid}', [
    '_controller' => Controller\Crates\ShowAction::class,
]));

// Ports
$collection->add('ports_list', new Route('/ports', [
    '_controller' => Controller\Ports\ListAction::class,
]));

$collection->add('ports_show', new Route('/ports/{uuid}', [
    '_controller' => Controller\Ports\ShowAction::class,
]));

$collection->add('ports_crates', new Route('/ports/{uuid}/crates', [
    '_controller' => Controller\Ports\CratesAction::class,
]));

// ships
$collection->add('ships_list', new Route('/ships', [
    '_controller' => Controller\Ships\ListAction::class,
]));

$collection->add('ships_show', new Route('/ships/{uuid}', [
    '_controller' => Controller\Ships\ShowAction::class,
]));

// Play - requires cookie
$collection->add('play_status', new Route('/play', [
    '_controller' => Controller\Play\IndexAction::class,
]));

$collection->add('play_positions', new Route('/play/ships', [
    '_controller' => Controller\Play\Ships\ListAction::class,
]));

$collection->add('play_positions_ship', new Route('/play/ships/{uuid}', [
    '_controller' => Controller\Play\Ships\ShowAction::class,
]));

// actions
$collection->add('action_move_ship', new Route('/action/move-ship', [
    '_controller' => Controller\Actions\MoveShipAction::class,
]));

return $collection;
