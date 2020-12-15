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
                echo \crisp\core\PluginAPI::response(false, "Exported", \crisp\api\Translation::fetchAll(), JSON_PRETTY_PRINT);
                break;
            default:
                echo \crisp\core\PluginAPI::response(["INVALID_OPTIONS"], $Query, []);
        }
        break;
    case "badgepng":
    case "badge":
        $render = new SvgPlasticRender();
        $poser = new Poser($render);

        if (!is_numeric($Query)) {
            if (!\crisp\api\Phoenix::serviceExistsBySlugPG($Query)) {
                header("Content-Type: image/svg+xml");
                $Color = "999999";
                $Rating = "Service not found";

                echo $poser->generate(\crisp\api\Config::get("badge_prefix"), $Rating, $Color, 'plastic');
                return;
            }
            $RedisData = \crisp\api\Phoenix::getServiceBySlugPG($Query);

            $Color;

            switch ($RedisData["is_comprehensively_reviewed"] ? ($RedisData["rating"]) : false) {
                case "A":
                    $Color = "46A546";
                    $Rating = "Class A";
                    break;
                case "B":
                    $Color = "79B752";
                    $Rating = "Class B";
                    break;
                case "C":
                    $Color = "F89406";
                    $Rating = "Class C";
                    break;
                case "D":
                    $Color = "D66F2C";
                    $Rating = "Class D";
                    break;
                case "E":
                    $Color = "C43C35";
                    $Rating = "Class E";
                    break;
                default:
                    $Color = "999999";
                    $Rating = "No Class Yet";
            }

            $SVG = $poser->generate(\crisp\api\Config::get("badge_prefix") . "/#" . $RedisData["slug"], $Rating, $Color, 'plastic');

            if (time() - filemtime(__DIR__ . "/badges/" . $RedisData["id"] . ".svg") > 900) {
                file_put_contents(__DIR__ . "/badges/" . $RedisData["id"] . ".svg", $SVG);
            }

            if ($_GET["apiversion"] === "badgepng") {
                header("Content-Type: image/png");
                // inkscape -e facebook.png facebook.svg

                if (!file_exists(__DIR__ . "/badges/" . $RedisData["id"] . ".svg")) {
                    exit;
                }

                if (time() - filemtime(__DIR__ . "/badges/" . $RedisData["id"] . ".svg") > 900) {

                    exec("/usr/bin/inkscape -e \"" . __DIR__ . "/badges/" . $RedisData["id"] . ".png\" \"" . __DIR__ . "/badges/" . $RedisData["id"] . ".svg\"");

                    if (!file_exists(__DIR__ . "/badges/" . $RedisData["id"] . ".png")) {
                        exit;
                    }
                }
                echo file_get_contents(__DIR__ . "/badges/" . $RedisData["id"] . ".png");
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

            echo $poser->generate(\crisp\api\Config::get("badge_prefix"), $Rating, $Color, 'plastic');
            return;
        }
        $RedisData = crisp\api\Phoenix::getServicePG($Query);

        $Color;

        switch ($RedisData["is_comprehensively_reviewed"] ? ($RedisData["rating"]) : false) {
            case "A":
                $Color = "46A546";
                $Rating = "Class A";
                break;
            case "B":
                $Color = "79B752";
                $Rating = "Class B";
                break;
            case "C":
                $Color = "F89406";
                $Rating = "Class C";
                break;
            case "D":
                $Color = "D66F2C";
                $Rating = "Class D";
                break;
            case "E":
                $Color = "C43C35";
                $Rating = "Class E";
                break;
            default:
                $Color = "999999";
                $Rating = "No Class Yet";
        }
        header("Content-Type: image/svg+xml");


        echo file_get_contents(__DIR__ . "/badges/" . $RedisData["id"] . ".png");
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
