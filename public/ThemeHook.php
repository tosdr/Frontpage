<?php

use crisp\api\Helper;
use crisp\models\HookFile;
use \Twig\Environment;
use crisp\core\Router;
use crisp\core\Themes;
use crisp\types\RouteType;
use tosdr\PageControllers\AboutPageController;
use tosdr\PageControllers\DownloadPageController;
use tosdr\PageControllers\FrontpagePageController;
use tosdr\Phoenix;
use \Twig\TwigFunction;
use Unleash\Client\UnleashBuilder;
use Unleash\Client\Configuration\UnleashContext;
use Cache\Adapter\Filesystem\FilesystemCachePool;
use crisp\api\Build;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use tosdr\Unleash\CustomContextProvider;

class ThemeHook
{

    public static function setLocale($locale)
    {

        if ($locale === Helper::getLocale()) {
            return;
        }
        $origurl = parse_url(Helper::currentURL());

        $parameters = $_GET;

        $parameters["crisp_locale"] = $locale;

        $url = sprintf("%s://%s%s%s", $_ENV["PROTO"], $origurl["host"], $origurl["path"], (count($parameters) > 0 ? "?" : "") . http_build_query($parameters));

        header("Location: $url");
        exit;
    }

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


        $unleash = UnleashBuilder::create()
            ->withCacheTimeToLive(120)
            ->withStaleTtl(300)
            ->withAppUrl($_ENV["UNLEASH_API_URL"])
            ->withInstanceId($_ENV["UNLEASH_INSTANCE_ID"])
            ->withContextProvider(new CustomContextProvider())
            ->withGitlabEnvironment(ENVIRONMENT)
            ->withAutomaticRegistrationEnabled(false)
            ->withMetricsEnabled(false)
            ->build();

        Themes::getRenderer()->addFunction(new TwigFunction('isFeatureEnabled', [$unleash, 'isEnabled']));
        Themes::getRenderer()->addFunction(new TwigFunction('getService', [Phoenix::class, 'getService']));
        Themes::getRenderer()->addFunction(new TwigFunction('getPoint', [Phoenix::class, 'getPoint']));
        Themes::getRenderer()->addFunction(new TwigFunction('getPointsByService', [Phoenix::class, 'getPointsByService']));
        Themes::getRenderer()->addFunction(new TwigFunction('getCase', [Phoenix::class, 'getCase']));
        Themes::getRenderer()->addFunction(new TwigFunction('getPointsByServiceScored', [Phoenix::class, 'getPointsByServiceScored']));


        # Public Routes
        Router::add("/", RouteType::PUBLIC, FrontpagePageController::class);
        Router::add("/{locale}/about", RouteType::PUBLIC, AboutPageController::class);
        Router::add("/about", RouteType::PUBLIC, AboutPageController::class);


        Router::add("/{locale}/download", RouteType::PUBLIC, DownloadPageController::class);
        Router::add("/download", RouteType::PUBLIC, DownloadPageController::class);
        Router::add("/{locale}/downloads", RouteType::PUBLIC, DownloadPageController::class);
        Router::add("/downloads", RouteType::PUBLIC, DownloadPageController::class);
    }
}
