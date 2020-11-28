<?php

define('CRISP_API', true);
require_once __DIR__ . "/../pixelcatproductions/crisp.php";

use PUGX\Poser\Render\SvgPlasticRender;
use PUGX\Poser\Poser;

$Redis = new \crisp\core\Redis();

$Redis = $Redis->getDBConnector();

$ServiceID = $_GET["q"];

if (strpos($_GET["q"], ".json")) {
    $ServiceID = substr($_GET["q"], 0, -5);
}


switch ($_GET["apiversion"]) {
    case "export":
        header("Content-Type: application/json");
        if ($ServiceID == "translations") {
            echo json_encode(\crisp\api\Translation::fetchAll(), JSON_PRETTY_PRINT);
        }
        break;
    case "badge":
        header("Content-Type: image/svg+xml");
        $render = new SvgPlasticRender();
        $poser = new Poser($render);

        if (count($Redis->keys(\crisp\api\Config::get("phoenix_api_endpoint") . "/services/name/" . strtolower($ServiceID))) === 0) {
            echo json_encode(array("error" => true, "message" => "This service does not exist!"));
            return;
        }
        $RedisData = json_decode($Redis->get(\crisp\api\Config::get("phoenix_api_endpoint") . "/services/name/" . strtolower($ServiceID)));

        $Color;

        switch ($RedisData->rating) {
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

        echo $poser->generate(\crisp\api\Config::get("badge_prefix"), $Rating, $Color, 'plastic');
        break;
    case "1":
    default:
        header("Content-Type: application/json");

        if ($ServiceID == "all") {
            $Services = crisp\api\Phoenix::getServices();
            $Response = array(
                "tosdr/api/version" => 1,
                "tosdr/data/version" => time(),
            );
            foreach ($Services->services as $Service) {
                $URLS = explode(",", $Service->url);
                foreach ($URLS as $URL) {
                    $URL = trim($URL);
                    $Response["tosdr/review/$URL"] = array(
                        "documents" => [],
                        "logo" => "https://$_SERVER[HTTP_HOST]/" . \crisp\api\Config::get("theme_dir") . "/" . \crisp\api\Config::get("theme") . "/img/logo/" . \crisp\api\Helper::filterAlphaNum($Service->name) . ".png",
                        "name" => $Service->name,
                        "slug" => $Service->name,
                        "rated" => ($Service->rating == "N/A" ? false : $Service->rating),
                        "points" => []
                    );
                }
            }
            echo json_encode($Response);
            return;
        }

        if (count($Redis->keys(\crisp\api\Config::get("phoenix_api_endpoint") . "/services/name/" . strtolower($ServiceID))) === 0) {
            echo json_encode(array("error" => true, "message" => "This service does not exist!"));
            return;
        }
        $RedisData = json_decode($Redis->get(\crisp\api\Config::get("phoenix_api_endpoint") . "/services/name/" . strtolower($ServiceID)));

        $AlexaRank;
        $ServiceLinks = array();
        $ServicePoints = array();
        $ServicePointsData = array();

        /*
         * //root/links
         */
        foreach ($RedisData->documents as $Links) {
            $ServiceLinks[$Links->name] = array(
                "name" => $Links->name,
                "url" => $Links->url
            );
        }
        foreach ($RedisData->points as $Point) {
            if ($Point->status == "approved") {
                array_push($ServicePoints, $Point->id);
            }
        }
        foreach ($RedisData->points as $Point) {
            $Document = array_column($RedisData->documents, null, 'id')[$Point->document_id];
            $Case = crisp\api\Phoenix::getCase($Point->case_id);
            if ($Point->status == "approved") {
                $ServicePointsData[$Point->id] = array(
                    "discussion" => "https://edit.tosdr.org/points/" . $Point->id,
                    "id" => $Point->id,
                    "needsModeration" => ($Point->status != "approved"),
                    "quoteDoc" => $Document->name,
                    "quoteText" => $Point->quoteText,
                    "quoteStart" => $Point->quoteStart,
                    "quoteEnd" => $Point->quoteEnd,
                    "services" => array($ServiceID),
                    "set" => "set+service+and+topic",
                    "slug" => $Point->id,
                    "title" => $Point->title,
                    "topics" => array(),
                    "tosdr" => array(
                        "binding" => true,
                        "case" => $Case->title,
                        "point" => $Case->classification,
                        "score" => -1,
                        "tldr" => $Point->analysis
                    ),
                );
            }
        }

        $SkeletonData = array(
            "alexa" => $AlexaRank,
            "class" => $RedisData->rating,
            "links" => $ServiceLinks,
            "points" => $ServicePoints,
            "pointsData" => $ServicePointsData,
            "urls" => explode(",", $RedisData->url)
        );

        echo json_encode($SkeletonData);


        break;
}
