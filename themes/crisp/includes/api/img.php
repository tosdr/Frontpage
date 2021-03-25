<?php

header("Access-Control-Allow-Origin: *");


if (!isset($GLOBALS["route"]->GET["theme"])) {
    \crisp\api\Helper::PlaceHolder("Invalid Theme");
}
if (!isset($GLOBALS["route"]->GET["logo"])) {
    \crisp\api\Helper::PlaceHolder("Invalid Service");
}

if (file_exists(__DIR__ . "/../themes/" . $GLOBALS["route"]->GET["theme"] . "/img/logo/" . explode("?", $GLOBALS["route"]->GET["logo"])[0])) {
    $ext = pathinfo(__DIR__ . "/../themes/" . $GLOBALS["route"]->GET["theme"] . "/img/logo/" . explode("?", $GLOBALS["route"]->GET["logo"])[0], PATHINFO_EXTENSION);
    if ($ext == "png") {
        header("Content-Type: image/png");
    }
    if ($ext == "svg") {
        header("Content-Type: image/svg+xml");
    }
    if ($ext == "jpg") {
        header("Content-Type: image/jpg");
    }
    echo file_get_contents(__DIR__ . "/../themes/" . $GLOBALS["route"]->GET["theme"] . "/img/logo/" . explode("?", $GLOBALS["route"]->GET["logo"])[0]);
} else {
    \crisp\api\Helper::PlaceHolder("Missing Logo");
}