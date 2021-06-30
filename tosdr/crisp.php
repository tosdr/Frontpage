<?php

/*
 * Copyright (C) 2021 Justin René Back <justin@tosdr.org>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace crisp;

use CompileError;
use crisp\api\Config;
use crisp\api\Helper;
use crisp\api\lists\Languages;
use crisp\api\Phoenix;
use crisp\api\Translation;
use crisp\core\Bitmask;
use crisp\core\PluginAPI;
use crisp\core\Plugins;
use crisp\core\Redis;
use crisp\core\Security;
use crisp\core\Themes;
use crisp\exceptions\BitmaskException;
use Error;
use Exception;
use ParseError;
use RateLimit\Rate;
use RateLimit\RedisRateLimiter;
use Throwable;
use Twig\Environment;
use Twig\Extension\StringLoaderExtension;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;
use Twig\TwigFunction;
use TypeError;

define('CRISP_COMPONENT', true);

/**
 * Core class, nothing else
 *
 * @author Justin Back <justin@tosdr.org>
 */
class core
{
    /* Some important constants */

    public const CRISP_VERSION = '5.0.0';

    public const API_VERSION = '2.2.0';

    /**
     * This is my autoloader.
     * There are many like it, but this one is mine.
     * My autoloader is my best friend.
     * It is my life.
     * I must master it as I must master my life.
     * My autoloader, without me, is useless.
     * Without my autoloader, I am useless.
     * I must use my autoloader true.
     * I must code better than my enemy who is trying to be better than me.
     * I must be better than him before he is.
     * And I will be.
     *
     */
    public static function bootstrap(): void
    {
        spl_autoload_register(static function ($class) {
            $file = __DIR__ . '/class/' . str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';

            if (file_exists($file)) {
                require $file;
                return true;
            }
            return false;
        });
        /** Core headers, can be accessed anywhere */
        header('X-Cluster: ' . gethostname());
        /** After autoloading we include additional headers below */
    }

}

if ($_SERVER['ENVIRONMENT'] === 'development') {
    define('IS_DEV_ENV', true);
} else {
    define('IS_DEV_ENV', false);
}

try {
    require_once __DIR__ . '/../vendor/autoload.php';
    core::bootstrap();
    if (PHP_SAPI !== 'cli') {

        $GLOBALS['route'] = api\Helper::processRoute($_GET['route']);

        $GLOBALS['microtime'] = [];
        $GLOBALS['microtime']['logic'] = [];
        $GLOBALS['microtime']['template'] = [];

        $GLOBALS['microtime']['logic']['start'] = microtime(true);

        $GLOBALS['plugins'] = [];
        $GLOBALS['hook'] = [];
        $GLOBALS['navbar'] = [];
        $GLOBALS['navbar_right'] = [];
        $GLOBALS['render'] = [];

        session_start();

        $CurrentTheme = Config::get('theme');
        $CurrentFile = substr(substr($_SERVER['PHP_SELF'], 1), 0, -4);
        $CurrentPage = $GLOBALS['route']->Page;
        $CurrentPage = ($CurrentPage === '' ? 'frontpage' : $CurrentPage);
        $CurrentPage = explode('.', $CurrentPage)[0];
        $Simple = (explode('.', $_SERVER['HTTP_HOST'])[0] === 'simple');

        define('IS_API_ENDPOINT', explode('/', $_GET['route'])[1] === 'api' || isset($_SERVER['IS_API_ENDPOINT']));
        define('IS_NATIVE_API', isset($_SERVER['IS_API_ENDPOINT']));

        if (isset($_GET['optin']) && is_numeric($_GET['optin'])) {
            Experiments::optIn($_GET['optin']);
            $_notice = [
                'Icon' => 'fas fa-flask',
                'Type' => 'warning',
                'Text' => Translation::fetch('experiment.optin', 1, ['{{ optout }}' => Helper::generateLink('?optout=' . $_GET['optin'])])
            ];
        } else if (isset($_GET['optout']) && is_numeric($_GET['optout'])) {
            Experiments::optOut($_GET['optout']);
            $_notice = [
                'Icon' => 'fas fa-flask',
                'Type' => 'warning',
                'Text' => Translation::fetch('experiment.optout')
            ];
        }
        if (isset($_GET['universe'])) {
            if ($_GET['universe'] === '3') {
                $authorized = Helper::authorizeAction('universe_dev', 'comp-staff');
                if ($authorized) {
                    Universe::changeUniverse($_GET['universe']);
                    $_notice = [
                        'Icon' => 'fas fa-exclamation',
                        'Text' => Translation::fetch('universe.switched')
                    ];
                } else {
                    $_notice = [
                        'Icon' => 'fas fa-exclamation',
                        'Text' => Translation::fetch('universe.switched.fail')
                    ];
                }
            } else {
                Universe::changeUniverse($_GET['universe']);
                $_notice = [
                    'Icon' => 'fas fa-exclamation',
                    'Text' => Translation::fetch('universe.switched')
                ];
            }
        } elseif (!isset($_COOKIE[core\Config::$Cookie_Prefix . 'universe'])) {
            Universe::changeUniverse(Universe::UNIVERSE_PUBLIC);
        }

        define('CURRENT_UNIVERSE', Universe::getUniverse($_COOKIE[core\Config::$Cookie_Prefix . 'universe']));
        define('CURRENT_UNIVERSE_NAME', Universe::getUniverseName(CURRENT_UNIVERSE));

        $ThemeLoader = new FilesystemLoader([__DIR__ . "/../themes/$CurrentTheme/templates/", __DIR__ . '/../plugins/']);

        if (CURRENT_UNIVERSE <= Universe::UNIVERSE_BETA) {
            if (!$Simple) {
                $TwigTheme = new Environment($ThemeLoader, [
                    'cache' => __DIR__ . '/cache/'
                ]);
            } else {
                $TwigTheme = new Environment($ThemeLoader, [
                    'cache' => __DIR__ . '/cache/simple/'
                ]);
            }
        } else {
            $TwigTheme = new Environment($ThemeLoader, []);
        }

        Experiments::assignAB();


        api\Helper::setLocale();
        $Locale = Helper::getLocale();

        header("X-CMS-CurrentPage: $CurrentPage");
        header("X-CMS-Locale: $Locale");
        header('X-CMS-Universe: ' . CURRENT_UNIVERSE);
        header('X-CMS-Universe-Human: ' . CURRENT_UNIVERSE_NAME);


        $TwigTheme->addGlobal('HAS_EXPERIMENT', Experiments::hasAnyExperiment());
        $TwigTheme->addGlobal('EXPERIMENTS', Experiments::getExperiments());
        $TwigTheme->addGlobal('config', Config::list());
        $TwigTheme->addGlobal('locale', $Locale);
        $TwigTheme->addGlobal('languages', Translation::listLanguages(false));
        $TwigTheme->addGlobal('GET', $_GET);
        $TwigTheme->addGlobal('UNIVERSE', CURRENT_UNIVERSE);
        $TwigTheme->addGlobal('UNIVERSE_NAME', CURRENT_UNIVERSE_NAME);
        $TwigTheme->addGlobal('CurrentPage', $CurrentPage);
        $TwigTheme->addGlobal('POST', $_POST);
        $TwigTheme->addGlobal('SERVER', $_SERVER);
        $TwigTheme->addGlobal('GLOBALS', $GLOBALS);
        $TwigTheme->addGlobal('ONLY_TOSDR_ASSETS', isset($_SERVER['HTTP_DNT']));
        if (isset($_notice)) {
            $TwigTheme->addGlobal('Notice', $_notice);
        }
        $TwigTheme->addGlobal('COOKIE', $_COOKIE);
        $TwigTheme->addGlobal('SIMPLE', $Simple);
        $TwigTheme->addGlobal('isMobile', Helper::isMobile());
        $TwigTheme->addGlobal('CLUSTER', gethostname());
        $TwigTheme->addGlobal('THEME_MODE', Themes::getThemeMode());

        $TwigTheme->addExtension(new StringLoaderExtension());

        $TwigTheme->addFunction(new TwigFunction('getService', [new Phoenix(), 'getService']));
        $TwigTheme->addFunction(new TwigFunction('getPoint', [new Phoenix(), 'getPoint']));
        $TwigTheme->addFunction(new TwigFunction('getPointsByService', [new Phoenix(), 'getPointsByService']));
        $TwigTheme->addFunction(new TwigFunction('getPointsByServiceScored', [new Phoenix(), 'getPointsByServiceScored']));
        $TwigTheme->addFunction(new TwigFunction('getCase', [new Phoenix(), 'getCase']));
        $TwigTheme->addFunction(new TwigFunction('prettyDump', [new Helper(), 'prettyDump']));
        $TwigTheme->addFunction(new TwigFunction('microtime', 'microtime'));
        $TwigTheme->addFunction(new TwigFunction('includeResource', [new Themes(), 'includeResource']));
        $TwigTheme->addFunction(new TwigFunction('generateLink', [new Helper(), 'generateLink']));

        /* CSRF Stuff */
        $TwigTheme->addFunction(new TwigFunction('csrf', [new Security(), 'getCSRF']));
        $TwigTheme->addFunction(new TwigFunction('refreshCSRF', [new Security(), 'regenCSRF']));
        $TwigTheme->addFunction(new TwigFunction('validateCSRF', [new Security(), 'matchCSRF']));

        $Translation = new Translation($Locale);

        $TwigTheme->addFilter(new TwigFilter('date', 'date'));
        $TwigTheme->addFilter(new TwigFilter('bcdiv', 'bcdiv'));
        $TwigTheme->addFilter(new TwigFilter('integer', 'intval'));
        $TwigTheme->addFilter(new TwigFilter('double', 'doubleval'));
        $TwigTheme->addFilter(new TwigFilter('json', 'json_decode'));
        $TwigTheme->addFilter(new TwigFilter('json_encode', 'json_encode'));
        $TwigTheme->addFilter(new TwigFilter('json_decode', 'json_decode'));
        $TwigTheme->addFilter(new TwigFilter('translate', [$Translation, 'fetch']));
        $TwigTheme->addFilter(new TwigFilter('getlang', [new Languages(), 'getLanguageByCode']));
        $TwigTheme->addFilter(new TwigFilter('truncateText', [new Helper(), 'truncateText']));

        $RedisClass = new Redis();
        $rateLimiter = new RedisRateLimiter($RedisClass->getDBConnector());

        if (file_exists(__DIR__ . "/../themes/$CurrentTheme/hook.php")) {
            require_once __DIR__ . "/../themes/$CurrentTheme/hook.php";
        }

        if (IS_API_ENDPOINT) {

            header('Access-Control-Allow-Origin: *');
            header('Cache-Control: max-age=600, public, must-revalidate');

            if (!isset($_SERVER['HTTP_USER_AGENT']) || empty($_SERVER['HTTP_USER_AGENT']) || $_SERVER['HTTP_USER_AGENT'] === 'i am not valid') {
                http_response_code(403);
                echo $TwigTheme->render('_prod/errors/nginx/403.twig', ['error_msg' => 'Request forbidden by administrative rules. Please make sure your request has a User-Agent header']);
                exit;
            }

            $Query = $GLOBALS['route']->GET['q'];

            if (empty($GLOBALS['route']->GET['q'])) {
                $Query = ($GLOBALS['route']->GET['service'] ?? 'no_query');
            }

            if (strpos($Query, '.json')) {
                $Query = substr($Query, 0, -5);
            }

            if (strlen($Query) === 0) {
                $Query = 'no_query';
            }


            $Benefit = 'Guest';
            $IndicatorSecond = 's_' . Helper::getRealIpAddr();
            $IndicatorHour = 'h_' . Helper::getRealIpAddr();
            $IndicatorDay = 'd_' . Helper::getRealIpAddr();

            $LimitSecond = Rate::perSecond(15);
            $LimitHour = Rate::perHour(1000);
            $LimitDay = Rate::perDay(15000);

            if (api\Helper::isApiKeyValid(Helper::getApiKey())) {


                $keyDetails = Helper::getAPIKeyDetails(Helper::getApiKey());


                if ($keyDetails['expires_at'] !== null && strtotime($keyDetails['expires_at']) < time()) {
                    header('X-APIKey: expired');
                } elseif ($keyDetails['revoked']) {
                    header('X-APIKey: revoked');
                } else {
                    header('X-APIKey: ok');
                }


                if ($keyDetails['ratelimit_second'] === null) {
                    $LimitSecond = Rate::perSecond(150);
                } else {
                    $LimitSecond = Rate::perSecond($keyDetails['ratelimit_second']);
                }
                if ($keyDetails['ratelimit_hour'] === null) {
                    $LimitHour = Rate::perHour(10000);
                } else {
                    $LimitHour = Rate::perHour($keyDetails['ratelimit_hour']);
                }

                if ($keyDetails['ratelimit_day'] === null) {
                    $LimitDay = Rate::perDay(50000);
                } else {
                    $LimitDay = Rate::perDay($keyDetails['ratelimit_day']);
                }

                $Benefit = $keyDetails['ratelimit_benefit'] ?? 'Partner';

            } else {
                header('X-APIKey: not-given');
                if (Helper::hasApiHeaders()) {
                    http_response_code(401);
                    echo $TwigTheme->render('_prod/errors/nginx/401.twig', ['error_msg' => 'Request forbidden by administrative rules. Please make sure your request has a valid Authorization or x-api-key header']);
                    exit;
                }

            }


            $statusSecond = $rateLimiter->limitSilently($IndicatorSecond, $LimitSecond);
            $statusHour = $rateLimiter->limitSilently($IndicatorHour, $LimitHour);
            $statusDay = $rateLimiter->limitSilently($IndicatorDay, $LimitDay);

            header('X-CMS-CDN: ' . api\Config::get('cdn'));
            header('X-CMS-SHIELDS: ' . api\Config::get('shield_cdn'));
            header('X-RateLimit-Benefit: ' . $Benefit);
            header('X-RateLimit-S: ' . $statusSecond->getRemainingAttempts());
            header('X-RateLimit-H: ' . $statusHour->getRemainingAttempts());
            header('X-RateLimit-D: ' . $statusDay->getRemainingAttempts());
            header('X-RateLimit-Benefit: ' . $Benefit);
            header('X-CMS-API: ' . api\Config::get('api_cdn'));
            header('X-CMS-API-VERSION: ' . core::API_VERSION);

            if ($statusSecond->limitExceeded() || $statusHour->limitExceeded() || $statusDay->limitExceeded()) {
                http_response_code(429);
                echo $TwigTheme->render('_prod/errors/nginx/429.twig', ['error_msg' => 'Request forbidden by administrative rules. You are sending too many requests in a certain timeframe.']);
                exit;
            }


            core\Themes::loadAPI($TwigTheme, $GLOBALS['route']->Page, $Query);
            core\Plugins::loadAPI($GLOBALS['route']->Page, $Query);
        }

        if (!$GLOBALS['route']->Language) {
            header("Location: /$Locale/$CurrentPage");
            exit;
        }

        Plugins::load($TwigTheme, $CurrentFile, $CurrentPage);
        Themes::load($TwigTheme, $CurrentFile, $CurrentPage);
    }
} catch (BitmaskException $ex) {
    http_response_code(500);
    $errorraw = file_get_contents(__DIR__ . '/../themes/emergency/error.html');
    try {


        $refid = api\ErrorReporter::create(500, $ex->getTraceAsString(), $ex->getMessage() . "\n\n" . api\Helper::currentURL(), $ex->getCode() . '_');

        if (IS_DEV_ENV) {
            $refid = $ex->getMessage();
        }


        if (IS_API_ENDPOINT) {
            PluginAPI::response(Bitmask::GENERIC_ERROR, 'Internal Server Error', ['reference_id' => $refid]);
            exit;
        }

        echo strtr($errorraw, ['{{ exception }}' => $refid]);
    } catch (Exception $ex2) {

        if (IS_API_ENDPOINT) {
            PluginAPI::response(Bitmask::GENERIC_ERROR, 'Internal Server Error', ['reference_id' => $ex2->getCode()]);
            exit;
        }

        echo strtr($errorraw, ['{{ exception }}' => $ex2->getCode()]);
        exit;
    }
} catch (TypeError | Exception | Error | CompileError | ParseError | Throwable $ex) {
    http_response_code(500);
    $errorraw = file_get_contents(__DIR__ . '/../themes/emergency/error.html');
    try {

        $refid = api\ErrorReporter::create(500, $ex->getTraceAsString(), $ex->getMessage() . "\n\n" . api\Helper::currentURL(), 'ca_');

        if (IS_DEV_ENV) {
            $refid = $ex->getMessage();
        }


        if (IS_API_ENDPOINT) {
            PluginAPI::response(Bitmask::GENERIC_ERROR, 'Internal Server Error', ['reference_id' => $refid]);
            exit;
        }

        echo strtr($errorraw, ['{{ exception }}' => $refid]);
        exit;
    } catch (Exception) {
        if (IS_API_ENDPOINT) {
            PluginAPI::response(Bitmask::GENERIC_ERROR, 'Internal Server Error');
            exit;
        }

        echo strtr($errorraw, ['{{ exception }}' => 'An error occurred... reporting the error?!?']);
        exit;
    }
}
