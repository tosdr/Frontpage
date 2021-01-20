<?php

header("Access-Control-Allow-Origin: *");
define('CRISP_API', true);
require_once __DIR__ . "/../pixelcatproductions/crisp.php";

use PUGX\Poser\Render\SvgFlatRender;
use PUGX\Poser\Poser;

$Query = $_GET["q"];

if (strpos($_GET["q"], ".json")) {
  $Query = substr($_GET["q"], 0, -5);
}

$RedisClass = new crisp\core\Redis();
\crisp\api\APIStats::add($_GET["apiversion"], $Query);
$rateLimiter = new \RateLimit\RedisRateLimiter($RedisClass->getDBConnector());

$Limit = \RateLimit\Rate::perMinute(100);
$Benefit = "guest";
$Indicator = \crisp\api\Helper::getRealIpAddr();

if (CURRENT_UNIVERSE == crisp\Universe::UNIVERSE_TOSDR || in_array(crisp\api\Helper::getRealIpAddr(), \crisp\api\Config::get("office_ips"))) {
  $Limit = \RateLimit\Rate::perSecond(10000);
  $Benefit = "staff";
  if (in_array(crisp\api\Helper::getRealIpAddr(), \crisp\api\Config::get("office_ips"))) {
    $Benefit = "office";
  }
}

$status = $rateLimiter->limitSilently($Indicator, $Limit);

header("X-RateLimit-Amount: " . $status->getRemainingAttempts());
header("X-RateLimit-Exceeded: " . ($status->limitExceeded() ? "true" : "false"));
header("X-RateLimit-Limit: " . $status->getLimit());
header("X-RateLimit-Interval: " . $Limit->getInterval());
header("X-RateLimit-Operations: " . $Limit->getOperations());
header("X-RateLimit-Indicator: $Indicator");
header("X-RateLimit-Reset: " . $status->getResetAt()->getTimestamp());
header("X-RateLimit-Benefit: " . $Benefit);

if ($status->limitExceeded()) {
  echo \crisp\core\PluginAPI::response(["RATE_LIMIT_REACHED"], "rate_limit", [], 429);
  exit;
}
switch ($_GET["apiversion"]) {
  case "export":
    switch ($Query) {
      case "translations":

        $Export = \crisp\api\Translation::fetchAll();

        foreach ($Export as $Language => $Translations) {
          foreach ($Translations as $Key => $Translation) {
            if (strpos($Key, "plugin_") !== false) {
              unset($Export[$Language][$Key]);
            }
          }
        }

        if (!isset($_GET["metadata"])) {
          echo \crisp\core\PluginAPI::response(false, "Exported", $Export, JSON_PRETTY_PRINT);
        } else {
          header("Content-Type: application/json");
          echo json_encode($Export);
        }
        break;
      default:
        echo \crisp\core\PluginAPI::response(["INVALID_OPTIONS"], $Query, []);
    }
    break;
  case "badgepng":
  case "badge":
    $render = new SvgFlatRender();
    $poser = new Poser($render);
    $Prefix = \crisp\api\Config::get("badge_prefix");
    $Language = (isset($_GET["l"]) ? $_GET["l"] : "en");
    $ServiceName = $Query;
    $Color;
    $Type = pathinfo($Query, PATHINFO_EXTENSION);
    $RedisData;

    if (strpos($Query, "_")) {
      $Language = explode("_", $Query)[0];
      $ServiceName = explode("_", $Query)[1];
    }
    if ($Type != "") {
      $ServiceName = substr($ServiceName, 0, (strlen($Type) + 1) * -1);
    }


    $Translations = new \crisp\api\Translation($Language);

    if (CURRENT_UNIVERSE >= crisp\Universe::UNIVERSE_DEV && isset($_GET["prefix"])) {
      $Prefix = $_GET["prefix"];
    }

    if (!is_numeric($ServiceName)) {
      if (!\crisp\api\Phoenix::serviceExistsBySlugPG(urldecode($ServiceName))) {
        header("Content-Type: image/svg+xml");
        $Color = "999999";
        $Rating = $Translation->fetch("service_not_found");

        echo $poser->generate($Prefix, $Rating, $Color, 'flat');
        return;
      }
      $RedisData = \crisp\api\Phoenix::getServiceBySlugPG(urldecode($ServiceName));
    } else {
      if (count(crisp\api\Phoenix::serviceExistsPG($ServiceName)) === 0) {
        header("Content-Type: image/svg+xml");
        $Color = "999999";
        $Rating = $Translation->fetch("service_not_found");

        echo $poser->generate($Prefix, $Rating, $Color, 'flat');
        return;
      }
      $RedisData = \crisp\api\Phoenix::getServicePG(urldecode($ServiceName));
    }


    switch ($RedisData["is_comprehensively_reviewed"] ? ($RedisData["rating"]) : false) {
      case "A":
        $Color = "46A546";
        $Rating = $Translations->fetch("privacy_grade_a");
        break;
      case "B":
        $Color = "79B752";
        $Rating = $Translations->fetch("privacy_grade_b");
        break;
      case "C":
        $Color = "F89406";
        $Rating = $Translations->fetch("privacy_grade_c");
        break;
      case "D":
        $Color = "D66F2C";
        $Rating = $Translations->fetch("privacy_grade_d");
        break;
      case "E":
        $Color = "C43C35";
        $Rating = $Translations->fetch("privacy_grade_e");
        break;
      default:
        $Color = "999999";
        $Rating = $Translations->fetch("privacy_grade_none");
    }

    $Prefix = \crisp\api\Config::get("badge_prefix") . "/#" . htmlentities($RedisData["slug"]);

    if (CURRENT_UNIVERSE >= crisp\Universe::UNIVERSE_DEV && isset($_GET["prefix"])) {
      $Prefix = $_GET["prefix"];
    }
    $SVG = $poser->generate($Prefix, $Rating, $Color, 'flat');

    if (time() - filemtime(__DIR__ . "/badges/" . sha1($Prefix . $RedisData["id"] . $Language) . ".svg") > 900) {
      file_put_contents(__DIR__ . "/badges/" . sha1($Prefix . $RedisData["id"] . $Language) . ".svg", $SVG);
    }

    if ($_GET["apiversion"] === "badgepng" || $Type == "png") {
      header("Content-Type: image/png");

      if (!file_exists(__DIR__ . "/badges/" . sha1($Prefix . $RedisData["id"] . $Language) . ".svg")) {
        exit;
      }

      if (time() - filemtime(__DIR__ . "/badges/" . sha1($Prefix . $RedisData["id"] . $Language) . ".png") > 900) {

        exec("/usr/bin/inkscape -e \"" . __DIR__ . "/badges/" . sha1($Prefix . $RedisData["id"] . $Language) . ".png\" \"" . __DIR__ . "/badges/" . sha1($Prefix . $RedisData["id"] . $Language) . ".svg\"");

        if (!file_exists(__DIR__ . "/badges/" . sha1($Prefix . $RedisData["id"] . $Language) . ".png")) {
          exit;
        }
      }
      echo file_get_contents(__DIR__ . "/badges/" . sha1($Prefix . $RedisData["id"] . $Language) . ".png");
      exit;
    }

    header("Content-Type: image/svg+xml");


    echo $SVG;
    break;

  case "updatecheck":

    $opts = [
        "http" => [
            "method" => "GET",
            "header" => "User-Agent: ToS;DR\r\n"
        ]
    ];

    $context = stream_context_create($opts);

    $Response = json_decode(file_get_contents("https://api.github.com/repos/tosdr/browser-extensions/releases/latest", false, $context));

    if (!isset($Query) || empty($Query)) {
      echo \crisp\core\PluginAPI::response(false, "Latest GitHub Release", ["release" => $Response->tag_name]);
      exit;
    } else {

      $Version = $Query;
      $Latest = $Response->tag_name;
      if (\crisp\api\Helper::startsWith($Query, "v")) {
        $Version = substr($Version, 1);
      }
      if (\crisp\api\Helper::startsWith($Latest, "v")) {
        $Latest = substr($Latest, 1);
      }

      echo \crisp\core\PluginAPI::response(false, "Comparing versions", ["latest" => $Latest, "given" => $Version, "substring" => \crisp\api\Helper::startsWith($Query, "v"), "compare" => version_compare($Latest, $Version)]);
    }
    break;

  case "topic_v1":
    if (!isset($Query) || empty($Query)) {
      echo \crisp\core\PluginAPI::response(false, $Query, crisp\api\Phoenix::getTopicsPG());
    } else {
      echo \crisp\core\PluginAPI::response(false, $Query, crisp\api\Phoenix::getTopicPG($Query));
    }
    break;
  case "case_v1":
    if (!isset($Query) || empty($Query)) {
      echo \crisp\core\PluginAPI::response(false, $Query, crisp\api\Phoenix::getCasesPG());
    } else {
      echo \crisp\core\PluginAPI::response(false, $Query, crisp\api\Phoenix::getCasePG($Query));
    }
    break;
  case "point_v1":
    if (!isset($Query) || empty($Query)) {
      echo \crisp\core\PluginAPI::response(false, $Query, crisp\api\Phoenix::getPointsPG());
    } else {
      echo \crisp\core\PluginAPI::response(false, $Query, crisp\api\Phoenix::getPointPG($Query));
    }
    break;
  case "2":
  case "1":
  case "v1":
  case "v2":
    header("Content-Type: application/json");

    if ($Query == "all") {
      $Services = \crisp\api\Phoenix::getServicesPG();
      $Response = array(
          "tosdr/api/version" => 1,
          "tosdr/data/version" => time(),
      );
      foreach ($Services as $Service) {
        $URLS = explode(",", $Service["url"]);
        foreach ($URLS as $URL) {
          $URL = trim($URL);
          $Response["tosdr/review/$URL"] = array(
              "id" => (int) $Service["id"],
              "documents" => [],
              "logo" => "https://$_SERVER[HTTP_HOST]/" . \crisp\api\Config::get("theme_dir") . "/" . \crisp\api\Config::get("theme") . "/img/logo/" . \crisp\api\Helper::filterAlphaNum($Service["name"]) . ".png",
              "name" => $Service["name"],
              "slug" => $Service["slug"],
              "rated" => ($Service["rating"] == "N/A" ? false : ($Service["is_comprehensively_reviewed"] ? $Service["rating"] : false)),
              "points" => []
          );
        }
      }
      echo json_encode($Response);
      return;
    }

    if (!is_numeric($Query)) {
      if (!crisp\api\Phoenix::serviceExistsBySlugPG($Query)) {
        echo \crisp\core\PluginAPI::response(["INVALID_SERVICE"], $Query, [], null, 404);
        return;
      }
      $Query = crisp\api\Phoenix::getServiceBySlugPG($Query)["id"];
      $SkeletonData = \crisp\api\Phoenix::generateApiFiles($Query);
      if ($_GET["apiversion"] === "1" || $_GET["apiversion"] === "v1") {
        echo json_encode($SkeletonData);
      } else {
        echo \crisp\core\PluginAPI::response(false, $Query, $SkeletonData);
      }

      exit;
    }



    if (!crisp\api\Phoenix::serviceExistsPG($Query)) {
      echo \crisp\core\PluginAPI::response(["INVALID_SERVICE"], $Query, [], null, 404);
      return;
    }

    $SkeletonData = \crisp\api\Phoenix::generateApiFiles($Query);

    if ($_GET["apiversion"] === "1" || $_GET["apiversion"] === "v1") {
      echo json_encode($SkeletonData);
    } else {
      echo \crisp\core\PluginAPI::response(false, $Query, $SkeletonData);
    }


    break;
  case "3":
  case "v3":


    if ($Query == "all") {
      $Services = \crisp\api\Phoenix::getServicesPG();
      $Response = array(
          "version" => time(),
      );
      foreach ($Services as $Index => $Service) {

        $Service["urls"] = explode(",", $Service["url"]);
        $Service["nice_service"] = \crisp\api\Helper::filterAlphaNum($Service["name"]);
        $Service["has_image"] = (file_exists(__DIR__ . "/../" . \crisp\api\Config::get("theme_dir") . "/" . \crisp\api\Config::get("theme") . "/img/logo/" . $Service["nice_service"] . ".svg") ? true : file_exists(__DIR__ . "/../" . \crisp\api\Config::get("theme_dir") . "/" . \crisp\api\Config::get("theme") . "/img/logo/" . $Service["nice_service"] . ".png") );
        $Service["logo"] = crisp\core\Themes::includeResource("img/logo/" . \crisp\api\Helper::filterAlphaNum($Service["name"]) . ".png");

        $Services[$Index] = $Service;
      }

      $Response["services"] = $Services;

      echo \crisp\core\PluginAPI::response(false, "All services below", $Response);

      return;
    }

    if (!is_numeric($Query)) {
      if (!crisp\api\Phoenix::serviceExistsBySlugPG($Query)) {
        echo \crisp\core\PluginAPI::response(["INVALID_SERVICE"], $Query, []);
        return;
      }
      $Query = crisp\api\Phoenix::getServiceBySlugPG($Query)["id"];
      $SkeletonData = \crisp\api\Phoenix::generateApiFiles($Query);
      echo \crisp\core\PluginAPI::response(false, $Query, \crisp\api\Phoenix::generateApiFiles($Query, "3"));
      exit;
    }

    if (!crisp\api\Phoenix::serviceExistsPG($Query)) {
      echo \crisp\core\PluginAPI::response(["INVALID_SERVICE"], $Query, []);
      return;
    }


    echo \crisp\core\PluginAPI::response(false, $Query, \crisp\api\Phoenix::generateApiFiles($Query, "3"));


    break;
  default:
    \crisp\core\Plugins::loadAPI($_GET["apiversion"], $Query);
    break;
}
