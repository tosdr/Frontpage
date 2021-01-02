<?php

/*
 * Copyright 2020 Pixelcat Productions <support@pixelcatproductions.net>
 * @author 2020 Justin René Back <jback@pixelcatproductions.net>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace crisp;

/**
 * Core class, nothing else
 *
 * @author Justin Back <jback@pixelcatproductions.net>
 */
class core {
    /* Some important constants */

    const CRISP_VERSION = "0.0.9-beta";

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
    public static function bootstrap() {
        spl_autoload_register(function ($class) {
            $file = __DIR__ . "/class/" . str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';


            if (file_exists($file)) {
                require $file;
                return true;
            }
            return false;
        });
    }

}

$GLOBALS["microtime"] = array();
$GLOBALS["microtime"]["logic"] = array();
$GLOBALS["microtime"]["template"] = array();

$GLOBALS["microtime"]["logic"]["start"] = microtime(true);

$GLOBALS["plugins"] = array();
$GLOBALS['hook'] = array();
$GLOBALS['navbar'] = array();
$GLOBALS['navbar_right'] = array();
$GLOBALS["render"] = array();
require_once __DIR__ . '/../vendor/autoload.php';


core::bootstrap();
session_start();

$CurrentTheme = \crisp\api\Config::get("theme");
$CurrentFile = substr(substr($_SERVER['PHP_SELF'], 1), 0, -4);
$CurrentPage = (isset($_GET["page"]) ? $_GET["page"] : substr($_SERVER["REQUEST_URI"], 1));
$CurrentPage = ($CurrentPage == "" ? "frontpage" : $CurrentPage);

if (isset($_GET["universe"])) {
    Universe::changeUniverse($_GET["universe"]);
} elseif (!isset($_SESSION[core\Config::$Cookie_Prefix . "universe"])) {
    Universe::changeUniverse(Universe::UNIVERSE_PUBLIC);
}

define("CURRENT_UNIVERSE", Universe::getUniverse($_SESSION[core\Config::$Cookie_Prefix . "universe"]));
define("CURRENT_UNIVERSE_NAME", Universe::getUniverseName(CURRENT_UNIVERSE));

if (!defined('CRISP_CLI')) {

    try {


        $ThemeLoader = new \Twig\Loader\FilesystemLoader(array(__DIR__ . "/../themes/$CurrentTheme/templates/", __DIR__ . "/../plugins/"));
        $TwigTheme;

        if (CURRENT_UNIVERSE <= Universe::UNIVERSE_BETA) {
            $TwigTheme = new \Twig\Environment($ThemeLoader, [
                'cache' => __DIR__ . '/cache/'
            ]);
        } else {
            $TwigTheme = new \Twig\Environment($ThemeLoader, []);
        }

        if (file_exists(__DIR__ . "/../themes/$CurrentTheme/hook.php")) {
            include __DIR__ . "/../themes/$CurrentTheme/hook.php";
        }



        api\Helper::setLocale();
        $Locale = \crisp\api\Helper::getLocale();

        $TwigTheme->addGlobal("config", \crisp\api\Config::list());
        $TwigTheme->addGlobal("locale", $Locale);
        $TwigTheme->addGlobal("languages", \crisp\api\Translation::listLanguages(false));
        $TwigTheme->addGlobal("GET", $_GET);
        $TwigTheme->addGlobal("UNIVERSE", CURRENT_UNIVERSE);
        $TwigTheme->addGlobal("UNIVERSE_NAME", CURRENT_UNIVERSE_NAME);
        $TwigTheme->addGlobal("CurrentPage", $CurrentPage);
        $TwigTheme->addGlobal("POST", $_POST);
        $TwigTheme->addGlobal("SERVER", $_SERVER);
        $TwigTheme->addGlobal("GLOBALS", $GLOBALS);
        $TwigTheme->addGlobal("COOKIE", $_COOKIE);
        $TwigTheme->addGlobal("isMobile", \crisp\api\Helper::isMobile());
        $TwigTheme->addGlobal("URL", api\Helper::currentDomain());


        $TwigTheme->addExtension(new \Twig\Extension\StringLoaderExtension());

        $TwigTheme->addFunction(new \Twig\TwigFunction('getGitRevision', [new \crisp\api\Helper(), 'getGitRevision']));
        $TwigTheme->addFunction(new \Twig\TwigFunction('getService', [new \crisp\api\Phoenix(), 'getServicePG']));
        $TwigTheme->addFunction(new \Twig\TwigFunction('getPoint', [new \crisp\api\Phoenix(), 'getPointPG']));
        $TwigTheme->addFunction(new \Twig\TwigFunction('getPointsByService', [new \crisp\api\Phoenix(), 'getPointsByServicePG']));
        $TwigTheme->addFunction(new \Twig\TwigFunction('getCase', [new \crisp\api\Phoenix(), 'getCasePG']));
        $TwigTheme->addFunction(new \Twig\TwigFunction('getGitBranch', [new \crisp\api\Helper(), 'getGitBranch']));
        $TwigTheme->addFunction(new \Twig\TwigFunction('prettyDump', [new \crisp\api\Helper(), 'prettyDump']));
        $TwigTheme->addFunction(new \Twig\TwigFunction('microtime', 'microtime'));
        $TwigTheme->addFunction(new \Twig\TwigFunction('includeResource', [new \crisp\core\Themes(), 'includeResource']));


        $Translation = new \crisp\api\Translation($Locale);

        $TwigTheme->addFilter(new \Twig\TwigFilter('date', 'date'));
        $TwigTheme->addFilter(new \Twig\TwigFilter('bcdiv', 'bcdiv'));
        $TwigTheme->addFilter(new \Twig\TwigFilter('integer', 'intval'));
        $TwigTheme->addFilter(new \Twig\TwigFilter('double', 'doubleval'));
        $TwigTheme->addFilter(new \Twig\TwigFilter('json', 'json_decode'));
        $TwigTheme->addFilter(new \Twig\TwigFilter('json_encode', 'json_encode'));
        $TwigTheme->addFilter(new \Twig\TwigFilter('translate', [$Translation, 'fetch']));
        $TwigTheme->addFilter(new \Twig\TwigFilter('getlang', [new \crisp\api\lists\Languages(), 'getLanguageByCode']));
        $TwigTheme->addFilter(new \Twig\TwigFilter('truncateText', [new \crisp\api\Helper(), 'truncateText']));


        $EnvFile = parse_ini_file(__DIR__ . "/../.env");

        if (!defined('CRISP_API')) {

            if (!isset($_GET["l"]) && !defined("CRISP_API")) {
                header("Location: /$Locale/$CurrentPage");
                exit;
            }

            \crisp\core\Plugins::load($TwigTheme, $CurrentFile, $CurrentPage);
            \crisp\core\Themes::load($TwigTheme, $CurrentFile, $CurrentPage);
        }
    } catch (\Exception $ex) {


        $TwigTheme = new \Twig\Environment($ThemeLoader, [
            'cache' => __DIR__ . '/cache/'
        ]);


        http_response_code(500);
        echo $TwigTheme->render("errors/exception.twig", array(
            "ReferenceID" => api\ErrorReporter::create(500, $ex->getTraceAsString(), $ex->getMessage(), "exception_")
        ));
        exit;
    } catch (\TypeError $ex) {


        $TwigTheme = new \Twig\Environment($ThemeLoader, [
            'cache' => __DIR__ . '/cache/'
        ]);


        http_response_code(500);
        echo $TwigTheme->render("errors/exception.twig", array(
            "ReferenceID" => api\ErrorReporter::create(500, $ex->getTraceAsString(), $ex->getMessage(), "typeerror_")
        ));
        exit;
    } catch (\Error $ex) {


        $TwigTheme = new \Twig\Environment($ThemeLoader, [
            'cache' => __DIR__ . '/cache/'
        ]);


        http_response_code(500);
        echo $TwigTheme->render("errors/exception.twig", array(
            "ReferenceID" => api\ErrorReporter::create(500, $ex->getTraceAsString(), $ex->getMessage(), "error_")
        ));
        exit;
    } catch (\CompileError $ex) {


        $TwigTheme = new \Twig\Environment($ThemeLoader, [
            'cache' => __DIR__ . '/cache/'
        ]);


        http_response_code(500);
        echo $TwigTheme->render("errors/exception.twig", array(
            "ReferenceID" => api\ErrorReporter::create(500, $ex->getTraceAsString(), $ex->getMessage(), "compileerror_")
        ));
        exit;
    } catch (\ParseError $ex) {


        $TwigTheme = new \Twig\Environment($ThemeLoader, [
            'cache' => __DIR__ . '/cache/'
        ]);


        http_response_code(500);
        echo $TwigTheme->render("errors/exception.twig", array(
            "ReferenceID" => api\ErrorReporter::create(500, $ex->getTraceAsString(), $ex->getMessage(), "parseerror_")
        ));
        exit;
    } catch (\Throwable $ex) {


        $TwigTheme = new \Twig\Environment($ThemeLoader, [
            'cache' => __DIR__ . '/cache/'
        ]);


        http_response_code(500);
        echo $TwigTheme->render("errors/exception.twig", array(
            "ReferenceID" => api\ErrorReporter::create(500, $ex->getTraceAsString(), $ex->getMessage(), "throwable_")
        ));
        exit;
    } catch (\Twig\Error\LoaderError $ex) {
        $Error = api\ErrorReporter::create(500, $ex->getTraceAsString(), $ex->getMessage(), "twigerror_");
        echo "Thats a twig error, cant even show a proper error page! Reference ID: $Error";
        exit;
    }
}