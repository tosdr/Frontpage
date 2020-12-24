<?php

header("Access-Control-Allow-Origin: *");
define('CRISP_API', true);
require_once __DIR__ . "/../pixelcatproductions/crisp.php";

use PUGX\Poser\Render\SvgPlasticRender;
use PUGX\Poser\Poser;

$Redis = new \crisp\core\Redis();

$Redis = $Redis->getDBConnector();

$Query = $_GET["q"];

if (strpos($_GET["q"], ".json")) {
    $Query = substr($_GET["q"], 0, -5);
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
                }else{
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
        $render = new SvgPlasticRender();
        $poser = new Poser($render);
        $Prefix = \crisp\api\Config::get("badge_prefix");

        if (CURRENT_UNIVERSE >= crisp\Universe::UNIVERSE_DEV && isset($_GET["prefix"])) {
            $Prefix = $_GET["prefix"];
        }

        if (!is_numeric($Query)) {
            if (!\crisp\api\Phoenix::serviceExistsBySlugPG($Query)) {
                header("Content-Type: image/svg+xml");
                $Color = "999999";
                $Rating = "Service not found";

                echo $poser->generate($Prefix, $Rating, $Color, 'plastic');
                return;
            }
            $RedisData = \crisp\api\Phoenix::getServiceBySlugPG($Query);

            $Color;

            switch ($RedisData["is_comprehensively_reviewed"] ? ($RedisData["rating"]) : false) {
                case "A":
                    $Color = "46A546";
                    $Rating = "Privacy Grade A";
                    break;
                case "B":
                    $Color = "79B752";
                    $Rating = "Privacy Grade B";
                    break;
                case "C":
                    $Color = "F89406";
                    $Rating = "Privacy Grade C";
                    break;
                case "D":
                    $Color = "D66F2C";
                    $Rating = "Privacy Grade D";
                    break;
                case "E":
                    $Color = "C43C35";
                    $Rating = "Privacy Grade E";
                    break;
                default:
                    $Color = "999999";
                    $Rating = "No Privacy Grade Yet";
            }

            $Prefix = \crisp\api\Config::get("badge_prefix") . "/#" . $RedisData["slug"];

            if (CURRENT_UNIVERSE >= crisp\Universe::UNIVERSE_DEV && isset($_GET["prefix"])) {
                $Prefix = $_GET["prefix"];
            }
            $SVG = $poser->generate($Prefix, $Rating, $Color, 'plastic');

            if (time() - filemtime(__DIR__ . "/badges/" . sha1($Prefix . $RedisData["id"]) . ".svg") > 900) {
                file_put_contents(__DIR__ . "/badges/" . sha1($Prefix . $RedisData["id"]) . ".svg", $SVG);
            }

            if ($_GET["apiversion"] === "badgepng") {
                header("Content-Type: image/png");
                // inkscape -e facebook.png facebook.svg

                if (!file_exists(__DIR__ . "/badges/" . sha1($Prefix . $RedisData["id"]) . ".svg")) {
                    exit;
                }

                if (time() - filemtime(__DIR__ . "/badges/" . sha1($Prefix . $RedisData["id"]) . ".png") > 900) {

                    exec("/usr/bin/inkscape -e \"" . __DIR__ . "/badges/" . sha1($Prefix . $RedisData["id"]) . ".png\" \"" . __DIR__ . "/badges/" . sha1($Prefix . $RedisData["id"]) . ".svg\"");

                    if (!file_exists(__DIR__ . "/badges/" . sha1($Prefix . $RedisData["id"]) . ".png")) {
                        exit;
                    }
                }
                echo file_get_contents(__DIR__ . "/badges/" . sha1($Prefix . $RedisData["id"]) . ".png");
                exit;
            }

            header("Content-Type: image/svg+xml");


            echo $SVG;
            return;
        }

        if (count(crisp\api\Phoenix::serviceExistsPG($Query)) === 0) {
            header("Content-Type: image/svg+xml");
            $Color = "999999";
            $Rating = "Service not found";

            echo $poser->generate($Prefix, $Rating, $Color, 'plastic');
            return;
        }
        $RedisData = crisp\api\Phoenix::getServicePG($Query);

        $Prefix = \crisp\api\Config::get("badge_prefix") . "/#" . $RedisData["slug"];

        if (CURRENT_UNIVERSE >= crisp\Universe::UNIVERSE_DEV && isset($_GET["prefix"])) {
            $Prefix = $_GET["prefix"];
        }
        $Color;

        switch ($RedisData["is_comprehensively_reviewed"] ? ($RedisData["rating"]) : false) {
            case "A":
                $Color = "46A546";
                $Rating = "Privacy Grade A";
                break;
            case "B":
                $Color = "79B752";
                $Rating = "Privacy Grade B";
                break;
            case "C":
                $Color = "F89406";
                $Rating = "Privacy Grade C";
                break;
            case "D":
                $Color = "D66F2C";
                $Rating = "Privacy Grade D";
                break;
            case "E":
                $Color = "C43C35";
                $Rating = "Privacy Grade E";
                break;
            default:
                $Color = "999999";
                $Rating = "No Privacy Grade Yet";
        }
        header("Content-Type: image/svg+xml");

        $SVG = $poser->generate($Prefix, $Rating, $Color, 'plastic');

        if (time() - filemtime(__DIR__ . "/badges/" . sha1($Prefix . $RedisData["id"]) . ".svg") > 900) {
            file_put_contents(__DIR__ . "/badges/" . sha1($Prefix . $RedisData["id"]) . ".svg", $SVG);
        }

        if ($_GET["apiversion"] === "badgepng") {
            header("Content-Type: image/png");
            // inkscape -e facebook.png facebook.svg

            if (!file_exists(__DIR__ . "/badges/" . sha1($Prefix . $RedisData["id"]) . ".svg")) {
                exit;
            }

            if (time() - filemtime(__DIR__ . "/badges/" . sha1($Prefix . $RedisData["id"]) . ".png") > 900) {

                exec("/usr/bin/inkscape -e \"" . __DIR__ . "/badges/" . sha1($Prefix . $RedisData["id"]) . ".png\" \"" . __DIR__ . "/badges/" . sha1($Prefix . $RedisData["id"]) . ".svg\"");

                if (!file_exists(__DIR__ . "/badges/" . sha1($Prefix . $RedisData["id"]) . ".png")) {
                    exit;
                }
            }
            echo file_get_contents(__DIR__ . "/badges/" . sha1($Prefix . $RedisData["id"]) . ".png");
            exit;
        }

        header("Content-Type: image/svg+xml");


        echo $SVG;
        break;
    case "2":
    case "1":
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
                        "documents" => [],
                        "logo" => "https://$_SERVER[HTTP_HOST]/" . \crisp\api\Config::get("theme_dir") . "/" . \crisp\api\Config::get("theme") . "/img/logo/" . \crisp\api\Helper::filterAlphaNum($Service["name"]) . ".png",
                        "name" => $Service["name"],
                        "slug" => $Service["name"],
                        "rated" => ($Service["rating"] == "N/A" ? false : $Service["rating"]),
                        "points" => []
                    );
                }
            }
            echo json_encode($Response);
            return;
        }

        if (!is_numeric($Query)) {
            if (!crisp\api\Phoenix::serviceExistsBySlugPG($Query)) {
                echo \crisp\core\PluginAPI::response(["INVALID_SERVICE"], $Query, []);
                return;
            }
            $Query = crisp\api\Phoenix::getServiceBySlugPG($Query)["id"];
            $SkeletonData = \crisp\api\Phoenix::generateApiFiles($Query);
            if ($_GET["apiversion"] === "1") {
                echo json_encode($SkeletonData);
            } else {
                echo \crisp\core\PluginAPI::response(false, $Query, $SkeletonData);
            }

            exit;
        }



        if (!crisp\api\Phoenix::serviceExistsPG($Query)) {
            echo \crisp\core\PluginAPI::response(["INVALID_SERVICE"], $Query, []);
            return;
        }

        $SkeletonData = \crisp\api\Phoenix::generateApiFiles($Query);

        if ($_GET["apiversion"] === "1") {
            echo json_encode($SkeletonData);
        } else {
            echo \crisp\core\PluginAPI::response(false, $Query, $SkeletonData);
        }


        break;
    default:
        \crisp\core\Plugins::loadAPI($_GET["apiversion"], $Query);
        break;
}
