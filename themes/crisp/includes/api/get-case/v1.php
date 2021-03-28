<?php

if (!crisp\api\Phoenix::getCasePG($_GET["case"] ?? $this->Query)) {
    echo \crisp\core\PluginAPI::response(\crisp\core\Bitmask::INVALID_CASE, $_GET["case"] ?? $this->Query, []);
    return;
}



echo \crisp\core\PluginAPI::response(\crisp\core\Bitmask::REQUEST_SUCCESS, $_GET["case"] ?? $this->Query, \crisp\api\Phoenix::getCasePG($_GET["case"] ?? $this->Query));