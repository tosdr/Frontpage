<?php

use crisp\api\Helper;
use crisp\models\HookFile;
use \Twig\Environment;
use crisp\core\Router;
use crisp\core\Themes;
use crisp\types\RouteType;
use tosdr\PageControllers\FrontpagePageController;
use tosdr\Phoenix;
use \Twig\TwigFunction;

class ThemeHook
{

    public function preExecute(): void
    {
    }
    public function postExecute(): void
    {
    }

    public function postRender(): void
    {
    }

    public function preRender(): void
    {
    }
    public function setup(): void
    {

        Themes::getRenderer()->addFunction(new TwigFunction('getService', [Phoenix::class, 'getService']));
        Themes::getRenderer()->addFunction(new TwigFunction('getPoint', [Phoenix::class, 'getPoint']));
        Themes::getRenderer()->addFunction(new TwigFunction('getPointsByService', [Phoenix::class, 'getPointsByService']));
        Themes::getRenderer()->addFunction(new TwigFunction('getCase', [Phoenix::class, 'getCase']));
        Themes::getRenderer()->addFunction(new TwigFunction('getPointsByServiceScored', [Phoenix::class, 'getPointsByServiceScored']));


        # Public Routes
        Router::add("/", RouteType::PUBLIC, FrontpagePageController::class);
    }
}
