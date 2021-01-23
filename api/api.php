<?php

use PUGX\Poser\Render\SvgFlatRender;
use PUGX\Poser\Poser;

$Query = (isset($GLOBALS["route"]->GET["q"]) ? $GLOBALS["route"]->GET["q"] : $GLOBALS["route"]->GET["service"]);

if (strpos($Query, ".json")) {
  $Query = substr($Query, 0, -5);
}

if (strlen($Query) === 0) {
  $Query = "no_query";
}

header("X-API-Interface: " . $GLOBALS["route"]->Page);
header("X-API-Query: $Query");

switch ($GLOBALS["route"]->Page) {
  case "badgepng":
  case "badge":
    $render = new SvgFlatRender();
    $poser = new Poser($render);
    $Prefix = \crisp\api\Config::get("badge_prefix");
    $Language = $GLOBALS["route"]->Language;
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
        $Rating = $Translations->fetch("badges.grade.a");
        break;
      case "B":
        $Color = "79B752";
        $Rating = $Translations->fetch("badges.grade.b");
        break;
      case "C":
        $Color = "F89406";
        $Rating = $Translations->fetch("badges.grade.c");
        break;
      case "D":
        $Color = "D66F2C";
        $Rating = $Translations->fetch("badges.grade.d");
        break;
      case "E":
        $Color = "C43C35";
        $Rating = $Translations->fetch("badges.grade.e");
        break;
      default:
        $Color = "999999";
        $Rating = $Translations->fetch("badges.grade.none");
    }

    header("X-API-Service: " . $RedisData["id"]);
    header("X-API-Service-Rating: " . $RedisData["rating"]);
    header("X-API-Service-Reviewed: " . $RedisData["is_comprehensively_reviewed"]);
    $Prefix = \crisp\api\Config::get("badge_prefix") . "/#" . htmlentities($RedisData["slug"]);

    $SVG = $poser->generate($Prefix, $Rating, $Color, 'flat');

    if (time() - filemtime(__DIR__ . "/badges/" . sha1($Prefix . $RedisData["id"] . $Language) . ".svg") > 900) {
      file_put_contents(__DIR__ . "/badges/" . sha1($Prefix . $RedisData["id"] . $Language) . ".svg", $SVG);
    }

    if ($GLOBALS["route"]->Page === "badgepng" || $Type == "png") {
      header("Content-Type: image/png");

      if (!file_exists(__DIR__ . "/badges/" . sha1($Prefix . $RedisData["id"] . $Language) . ".svg")) {
        echo \crisp\core\PluginAPI::response(["GENERATE_FAILED"], $Query, [], null, 500);
        exit;
      }

      if (time() - filemtime(__DIR__ . "/badges/" . sha1($Prefix . $RedisData["id"] . $Language) . ".png") > 900) {

        exec("/usr/bin/inkscape -e \"" . __DIR__ . "/badges/" . sha1($Prefix . $RedisData["id"] . $Language) . ".png\" \"" . __DIR__ . "/badges/" . sha1($Prefix . $RedisData["id"] . $Language) . ".svg\"");

        if (!file_exists(__DIR__ . "/badges/" . sha1($Prefix . $RedisData["id"] . $Language) . ".png")) {
          echo \crisp\core\PluginAPI::response(["GENERATE_FAILED"], $Query, [], null, 500);
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
              "logo" => crisp\core\Themes::includeResource("img/logo/" . \crisp\api\Helper::filterAlphaNum($Service["name"]) . ".png"),
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
      if ($GLOBALS["route"]->Page === "1" || $GLOBALS["route"]->Page === "v1") {
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

    if ($GLOBALS["route"]->Page === "1" || $GLOBALS["route"]->Page === "v1") {
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
  case "search":
    if (empty($Query)) {
      foreach (\crisp\api\Config::get("frontpage_services") as $ID) {
        $Array[] = crisp\api\Phoenix::getServicePG($ID);
      }
    } else {
      foreach (crisp\api\Phoenix::searchServiceByNamePG(strtolower($Query)) as $Service) {
        $Array[] = $Service;
      }
    }
    $Array = array_slice($Array, 0, 10);
    if (count($Array) > 0) {
      $cols = 2;
      if (crisp\api\Helper::isMobile()) {
        $cols = 1;
      }
      echo \crisp\core\PluginAPI::response(false, $Query, (array("service" => $Array, "grid" => $TwigTheme->render("components/servicegrid/grid.twig", array("Services" => $Array, "columns" => $cols)))));
      exit;
    }
    echo \crisp\core\PluginAPI::response(false, $Query, (array("service" => $Array, "grid" => $TwigTheme->render("components/servicegrid/no_service.twig", []))));

    break;
  case "img":
    header("Access-Control-Allow-Origin: *");


    if (!isset($GLOBALS["route"]->GET["theme"])) {
      \crisp\api\Helper::PlaceHolder("Invalid Theme");
    }
    if (!isset($GLOBALS["route"]->GET["logo"])) {
      \crisp\api\Helper::PlaceHolder("Invalid Service");
    }

    if (file_exists(__DIR__ . "/../themes/" . $GLOBALS["route"]->GET["theme"] . "/img/logo/" . $GLOBALS["route"]->GET["logo"])) {
      $ext = pathinfo(__DIR__ . "/../themes/" . $GLOBALS["route"]->GET["theme"] . "/img/logo/" . $GLOBALS["route"]->GET["logo"], PATHINFO_EXTENSION);
      if ($ext == "png") {
        header("Content-Type: image/png");
      }
      if ($ext == "svg") {
        header("Content-Type: image/svg+xml");
      }
      if ($ext == "jpg") {
        header("Content-Type: image/jpg");
      }
      echo file_get_contents(__DIR__ . "/../themes/" . $GLOBALS["route"]->GET["theme"] . "/img/logo/" . $GLOBALS["route"]->GET["logo"]);
    } else {
      \crisp\api\Helper::PlaceHolder("Missing Logo");
    }
    break;
  default:
    \crisp\core\Plugins::loadAPI($GLOBALS["route"]->Page, $Query);
    break;
}
