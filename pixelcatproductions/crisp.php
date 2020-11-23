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

$GLOBALS['hook'] = array();
require_once __DIR__ . '/../vendor/autoload.php';

core::bootstrap();
session_start();

$CurrentTheme = \crisp\api\Config::get("theme");
$CurrentFile = substr(substr($_SERVER['PHP_SELF'], 1), 0, -4);
$CurrentPage = substr($_SERVER["REQUEST_URI"], 1);


try {


    $ThemeLoader = new \Twig\Loader\FilesystemLoader(array(__DIR__ . "/../themes/$CurrentTheme/templates/", __DIR__ . "/../plugins/"));
    $TwigTheme = new \Twig\Environment($ThemeLoader, [
            /* 'cache' => __DIR__ . '/cache/' */
    ]);



    $Locale = locale_accept_from_http($_SERVER['HTTP_ACCEPT_LANGUAGE']);


    if (!in_array($Locale, array("en", "de"))) {
        $Locale = "en";
    }
    $Locale = "en";

    if (isset($_COOKIE[\crisp\core\Config::$Cookie_Prefix . "language"])) {
        $Locale = $_COOKIE[\crisp\core\Config::$Cookie_Prefix . "language"];
        setcookie(\crisp\core\Config::$Cookie_Prefix . "language", $Locale, time() + (86400 * 30), "/");
    }


    $TwigTheme->addGlobal("URL", "https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");
    $TwigTheme->addGlobal("config", \crisp\api\Config::list());
    $TwigTheme->addGlobal("locale", $Locale);
    $TwigTheme->addGlobal("languages", \crisp\api\Translation::listLanguages(false));
    $TwigTheme->addGlobal("GET", $_GET);
    $TwigTheme->addGlobal("POST", $_POST);
    $TwigTheme->addGlobal("SERVER", $_SERVER);
    $TwigTheme->addGlobal("COOKIE", $_COOKIE);


    $TwigTheme->addExtension(new \Twig\Extension\StringLoaderExtension());

    $TwigTheme->addFunction(new \Twig\TwigFunction('getGitRevision', [new \crisp\api\Helper(), 'getGitRevision']));
    $TwigTheme->addFunction(new \Twig\TwigFunction('getService', [new \crisp\api\Phoenix(), 'getService']));
    $TwigTheme->addFunction(new \Twig\TwigFunction('getPoint', [new \crisp\api\Phoenix(), 'getPoint']));
    $TwigTheme->addFunction(new \Twig\TwigFunction('getCase', [new \crisp\api\Phoenix(), 'getCase']));
    $TwigTheme->addFunction(new \Twig\TwigFunction('getGitBranch', [new \crisp\api\Helper(), 'getGitBranch']));


    $Translation = new \crisp\api\Translation($Locale);

    $TwigTheme->addFilter(new \Twig\TwigFilter('date', 'date'));
    $TwigTheme->addFilter(new \Twig\TwigFilter('bcdiv', 'bcdiv'));
    $TwigTheme->addFilter(new \Twig\TwigFilter('integer', 'intval'));
    $TwigTheme->addFilter(new \Twig\TwigFilter('double', 'doubleval'));
    $TwigTheme->addFilter(new \Twig\TwigFilter('json', 'json_decode'));
    $TwigTheme->addFilter(new \Twig\TwigFilter('translate', [$Translation, 'fetch']));
    $TwigTheme->addFilter(new \Twig\TwigFilter('getlang', [new \crisp\api\lists\Languages(), 'getLanguageByCode']));
    $TwigTheme->addFilter(new \Twig\TwigFilter('truncateText', [new \crisp\api\Helper(), 'truncateText']));
} catch (\Exception $ex) {

    $ThemeLoader = new \Twig\Loader\FilesystemLoader(array(__DIR__ . "/../themes/$CurrentTheme/templates/", __DIR__ . "/../plugins/"));
    $TwigTheme = new \Twig\Environment($ThemeLoader, [
            /* 'cache' => __DIR__ . '/cache/' */
    ]);


    $Locale = locale_accept_from_http($_SERVER['HTTP_ACCEPT_LANGUAGE']);

    if (!in_array($Locale, array("en", "de"))) {
        $Locale = "en";
    }

    if (isset($_COOKIE[\crisp\core\Config::$Cookie_Prefix . "language"])) {
        $Locale = $_COOKIE[\crisp\core\Config::$Cookie_Prefix . "language"];
        setcookie(\crisp\core\Config::$Cookie_Prefix . "language", $Locale, time() + (86400 * 30), "/");
    }
    $Translation = new \crisp\api\Translation($Locale);

    $TwigTheme->addFilter(new \Twig\TwigFilter('date', 'date'));
    $TwigTheme->addFilter(new \Twig\TwigFilter('getlang', [new \crisp\api\lists\Languages(), 'getLanguageByCode']));
    $TwigTheme->addFilter(new \Twig\TwigFilter('truncateText', [new \crisp\api\Helper(), 'truncateText']));

    $TwigTheme->addFilter(new \Twig\TwigFilter('translate', [$Translation, 'fetch']));
    $TwigTheme->addExtension(new \Twig\Extension\StringLoaderExtension());

    $TwigTheme->addGlobal("URL", "https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");
    $TwigTheme->addGlobal("config", \crisp\api\Config::list());
    $TwigTheme->addGlobal("locale", $Locale);
    $TwigTheme->addGlobal("languages", \crisp\api\Translation::listLanguages(false));
    $TwigTheme->addGlobal("GET", $_GET);
    $TwigTheme->addGlobal("POST", $_POST);
    $TwigTheme->addGlobal("SERVER", $_SERVER);
    $TwigTheme->addFunction(new \Twig\TwigFunction('getGitRevision', [new \crisp\api\Helper(), 'getGitRevision']));
    $TwigTheme->addFunction(new \Twig\TwigFunction('getGitBranch', [new \crisp\api\Helper(), 'getGitBranch']));


    echo $TwigTheme->render("errors/exception.twig", array(
        "error" => $ex->getMessage()
    ));
    exit;
} catch (\Twig\Error\LoaderError $ex) {
    var_dump($ex);
    exit;
}
$EnvFile = parse_ini_file(__DIR__ . "/../.env");

\crisp\core\Plugins::load($TwigTheme, $CurrentFile, $CurrentPage);
