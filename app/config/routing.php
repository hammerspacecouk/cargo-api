<?php
declare(strict_types=1);
use App\Controller;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;

$collection = new RouteCollection();
$collection->add('home', new Route('/', [
    '_controller' => Controller\Home\IndexAction::class,
]));

$collection->add('home_name', new Route('/{name}', [
    '_controller' => Controller\Home\NameAction::class,
]));

return $collection;
