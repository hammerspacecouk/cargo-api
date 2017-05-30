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


// Crates
$collection->add('crates_list', new Route('/crates', [
    '_controller' => Controller\Crates\ListAction::class,
]));

$collection->add('crates_show', new Route('/crates/{uuid}', [
    '_controller' => Controller\Crates\ShowAction::class,
]));

return $collection;
