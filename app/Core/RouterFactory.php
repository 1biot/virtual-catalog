<?php

declare(strict_types=1);

namespace App\Core;

use Nette;
use Nette\Application\Routers\RouteList;


final class RouterFactory
{
    use Nette\StaticClass;

    public static function createRouter(): RouteList
    {
        $router = new RouteList;
        $router->addRoute('catalog/search/<query>', 'Search:default');
        $router->addRoute('catalog/detail/<slug>', 'Detail:default');
        $router->addRoute('catalog/category/<slug>[/page/<page=1>]', 'Category:default');
        $router->addRoute('<presenter>/<action>[/<id>]', 'Home:default');
        return $router;
    }
}
