<?php

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    echo \crisp\core\PluginAPI::response(crisp\core\Bitmask::NOT_IMPLEMENTED, "Invalid Request Method", [], null, 405);
    exit;
}

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

echo \crisp\core\PluginAPI::response(crisp\core\Bitmask::REQUEST_SUCCESS, "All services below", $Response);
