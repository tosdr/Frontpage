<?php

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    echo \crisp\core\PluginAPI::response(crisp\core\Bitmask::NOT_IMPLEMENTED, "Invalid Request Method", [], null, 405);
    exit;
}

if (!is_numeric($_GET["service"] ?? $this->Query)) {
    if (!crisp\api\Phoenix::serviceExistsBySlugPG($_GET["service"] ?? $this->Query)) {
        echo \crisp\core\PluginAPI::response(\crisp\core\Bitmask::INVALID_SERVICE, $_GET["service"] ?? $this->Query, []);
        return;
    }
    $_GET["service"] ?? $this->Query = crisp\api\Phoenix::getServiceBySlugPG($_GET["service"] ?? $this->Query)["id"];
    $SkeletonData = \crisp\api\Phoenix::generateApiFiles($_GET["service"] ?? $this->Query);
    echo \crisp\core\PluginAPI::response(\crisp\core\Bitmask::REQUEST_SUCCESS, $_GET["service"] ?? $this->Query, \crisp\api\Phoenix::generateApiFiles($_GET["service"] ?? $this->Query, "3"));
    exit;
}

if (!crisp\api\Phoenix::serviceExistsPG($_GET["service"] ?? $this->Query)) {
    echo \crisp\core\PluginAPI::response(\crisp\core\Bitmask::INVALID_SERVICE, $_GET["service"] ?? $this->Query, []);
    return;
}



echo \crisp\core\PluginAPI::response(\crisp\core\Bitmask::REQUEST_SUCCESS, $_GET["service"] ?? $this->Query, \crisp\api\Phoenix::generateApiFiles($_GET["service"] ?? $this->Query, "3"));
