<?php

define('CRISP_API', true);
require_once __DIR__ . "/../pixelcatproductions/crisp.php";
header("Access-Control-Allow-Origin: *");


if (!isset($_GET["theme"])) {
    \crisp\api\Helper::PlaceHolder("Invalid Theme");
}
if (!isset($_GET["logo"])) {
    \crisp\api\Helper::PlaceHolder("Invalid Service");
}

if (file_exists(__DIR__ . "/../themes/" . $_GET["theme"] . "/img/logo/" . $_GET["logo"])) {
    $ext = pathinfo(__DIR__ . "/../themes/" . $_GET["theme"] . "/img/logo/" . $_GET["logo"], PATHINFO_EXTENSION);
    if ($ext == "png") {
        header("Content-Type: image/png");
    }
    if ($ext == "svg") {
        header("Content-Type: image/svg+xml");
    }
    if ($ext == "jpg") {
        header("Content-Type: image/jpg");
    }
    echo file_get_contents(__DIR__ . "/../themes/" . $_GET["theme"] . "/img/logo/" . $_GET["logo"]);
} else {
    \crisp\api\Helper::PlaceHolder("Missing Logo");
}