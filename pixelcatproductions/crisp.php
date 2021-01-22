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

  const CRISP_VERSION = "2.0.0";

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
    /** Core headers, can be accessed anywhere */
    header("X-Cluster: " . gethostname());
    /** After autoloading we include additional headers below */
  }

}

require_once __DIR__ . '/../vendor/autoload.php';
core::bootstrap();
if (php_sapi_name() !== "cli") {

  $GLOBALS["route"] = api\Helper::processRoute($_GET["route"]);

  $GLOBALS["microtime"] = array();
  $GLOBALS["microtime"]["logic"] = array();
  $GLOBALS["microtime"]["template"] = array();

  $GLOBALS["microtime"]["logic"]["start"] = microtime(true);

  $GLOBALS["plugins"] = array();
  $GLOBALS['hook'] = array();
  $GLOBALS['navbar'] = array();
  $GLOBALS['navbar_right'] = array();
  $GLOBALS["render"] = array();


  session_start();

  if (explode("/", $_GET["route"])[0] === "api") {
    define('CRISP_API', true);
  }


  $CurrentTheme = \crisp\api\Config::get("theme");
  $CurrentFile = substr(substr($_SERVER['PHP_SELF'], 1), 0, -4);
  $CurrentPage = $GLOBALS["route"]->Page;
  $CurrentPage = ($CurrentPage == "" ? "frontpage" : $CurrentPage);
  $CurrentPage = explode(".", $CurrentPage)[0];

  if (isset($_GET["universe"])) {
    Universe::changeUniverse($_GET["universe"]);
  } elseif (!isset($_COOKIE[core\Config::$Cookie_Prefix . "universe"])) {
    Universe::changeUniverse(Universe::UNIVERSE_PUBLIC);
  }

  define("CURRENT_UNIVERSE", Universe::getUniverse($_COOKIE[core\Config::$Cookie_Prefix . "universe"]));
  define("CURRENT_UNIVERSE_NAME", Universe::getUniverseName(CURRENT_UNIVERSE));

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

    if (CURRENT_UNIVERSE >= Universe::UNIVERSE_BETA) {
      if (isset($_GET["test_theme_component"])) {
        core\Themes::setThemeMode($_GET["test_theme_component"]);
      }
    } else {
      core\Themes::setThemeMode("0");
    }

    header("X-CMS-CurrentPage: $CurrentPage");
    header("X-CMS-Locale: $Locale");
    header("X-CMS-Universe: " . CURRENT_UNIVERSE);
    header("X-CMS-Universe-Human: " . CURRENT_UNIVERSE_NAME);

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
    $TwigTheme->addGlobal("CLUSTER", gethostname());
    $TwigTheme->addGlobal("THEME_MODE", \crisp\core\Themes::getThemeMode());

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
    $TwigTheme->addFunction(new \Twig\TwigFunction('generateLink', [new \crisp\api\Helper(), 'generateLink']));


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


    $RedisClass = new \crisp\core\Redis();
    $rateLimiter = new \RateLimit\RedisRateLimiter($RedisClass->getDBConnector());

    $Limit = \RateLimit\Rate::perSecond(15);
    $Benefit = "guest";
    $Indicator = \crisp\api\Helper::getRealIpAddr();

    if (CURRENT_UNIVERSE == \crisp\Universe::UNIVERSE_TOSDR || in_array(\crisp\api\Helper::getRealIpAddr(), \crisp\api\Config::get("office_ips"))) {
      $Limit = \RateLimit\Rate::perSecond(15000);
      $Benefit = "staff";
      if (in_array(\crisp\api\Helper::getRealIpAddr(), \crisp\api\Config::get("office_ips"))) {
        $Benefit = "office";
      }
    }

    $status = $rateLimiter->limitSilently($Indicator, $Limit);

    header("X-RateLimit-Amount: " . $status->getRemainingAttempts());
    header("X-RateLimit-Exceeded: " . ($status->limitExceeded() ? "true" : "false"));
    header("X-RateLimit-Limit: " . $status->getLimit());
    header("X-RateLimit-Interval: " . $Limit->getInterval());
    header("X-RateLimit-Indicator: $Indicator");
    header("X-RateLimit-Benefit: " . $Benefit);
    header("X-CMS-CDN: " . api\Config::get("cdn"));
    header("X-CMS-SHIELDS: " . api\Config::get("shield_cdn"));
    header("X-CMS-API: " . api\Config::get("api_cdn"));

    if ($status->limitExceeded() && !defined('CRISP_API')) {
      http_response_code(429);
      echo $TwigTheme->render("errors/ratelimit.twig", array(
          "ReferenceID" => api\ErrorReporter::create(429, $status->getResetAt()->getTimestamp(), $Benefit . "\n\n" . api\Helper::currentURL(), "ratelimit_")
      ));
      exit;
    } else if ($status->limitExceeded()) {
      echo \crisp\core\PluginAPI::response(["RATE_LIMIT_REACHED"], "rate_limit", [], 429);
      exit;
    }

    if (!isset($_SERVER['HTTP_USER_AGENT']) || empty($_SERVER['HTTP_USER_AGENT'])) {
      if (!defined('CRISP_API')) {
        http_response_code(403);
        echo $TwigTheme->render("errors/forbidden.twig", array(
            "ReferenceID" => api\ErrorReporter::create(403, "No Useragent", api\Helper::currentURL(), "badrequest_"),
            "ErrorText" => 'You must supply a user agent!'
        ));
      } else {
        echo \crisp\core\PluginAPI::response(["NO_USERAGENT_SUPPLIED"], "", [], null, 403);
      }
      exit;
    }

    if (defined('CRISP_API')) {
      include __DIR__ . "/../api/api.php";
    }

    if (!defined('CRISP_API')) {

      if (!$GLOBALS["route"]->Language && !defined("CRISP_API")) {
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
        "ReferenceID" => api\ErrorReporter::create(500, $ex->getTraceAsString(), $ex->getMessage() . "\n\n" . api\Helper::currentURL(), "exception_")
    ));
    exit;
  } catch (\TypeError $ex) {


    $TwigTheme = new \Twig\Environment($ThemeLoader, [
        'cache' => __DIR__ . '/cache/'
    ]);


    http_response_code(500);
    echo $TwigTheme->render("errors/exception.twig", array(
        "ReferenceID" => api\ErrorReporter::create(500, $ex->getTraceAsString(), $ex->getMessage() . "\n\n" . api\Helper::currentURL(), "typeerror_")
    ));
    exit;
  } catch (\Error $ex) {


    $TwigTheme = new \Twig\Environment($ThemeLoader, [
        'cache' => __DIR__ . '/cache/'
    ]);


    http_response_code(500);
    echo $TwigTheme->render("errors/exception.twig", array(
        "ReferenceID" => api\ErrorReporter::create(500, $ex->getTraceAsString(), $ex->getMessage() . "\n\n" . api\Helper::currentURL(), "error_")
    ));
    exit;
  } catch (\CompileError $ex) {


    $TwigTheme = new \Twig\Environment($ThemeLoader, [
        'cache' => __DIR__ . '/cache/'
    ]);


    http_response_code(500);
    echo $TwigTheme->render("errors/exception.twig", array(
        "ReferenceID" => api\ErrorReporter::create(500, $ex->getTraceAsString(), $ex->getMessage() . "\n\n" . api\Helper::currentURL(), "compileerror_")
    ));
    exit;
  } catch (\ParseError $ex) {


    $TwigTheme = new \Twig\Environment($ThemeLoader, [
        'cache' => __DIR__ . '/cache/'
    ]);


    http_response_code(500);
    echo $TwigTheme->render("errors/exception.twig", array(
        "ReferenceID" => api\ErrorReporter::create(500, $ex->getTraceAsString(), $ex->getMessage() . "\n\n" . api\Helper::currentURL(), "parseerror_")
    ));
    exit;
  } catch (\Throwable $ex) {


    $TwigTheme = new \Twig\Environment($ThemeLoader, [
        'cache' => __DIR__ . '/cache/'
    ]);


    http_response_code(500);
    echo $TwigTheme->render("errors/exception.twig", array(
        "ReferenceID" => api\ErrorReporter::create(500, $ex->getTraceAsString(), $ex->getMessage() . "\n\n" . api\Helper::currentURL(), "throwable_")
    ));
    exit;
  } catch (\Twig\Error\LoaderError $ex) {
    $Error = api\ErrorReporter::create(500, $ex->getTraceAsString(), $ex->getMessage() . "\n\n" . api\Helper::currentURL(), "twigerror_");
    echo "Thats a twig error, cant even show a proper error page! Reference ID: $Error";
    exit;
  }
}