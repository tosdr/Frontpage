<?php

require_once __DIR__ . "/../pixelcatproductions/crisp.php";

header("Content-Type: application/json");

$Redis = new \crisp\core\Redis();

$Redis = $Redis->getDBConnector();

$ServiceID = substr($_GET["q"], 0, -5);

switch ($_GET["apiversion"]) {
    case "1":
    default:


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
