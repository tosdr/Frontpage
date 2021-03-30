<?php

$Interface = "default";

if (is_array($GLOBALS["route"]->GET)) {
    $Interface = array_key_first($GLOBALS["route"]->GET);

    $this->Query = $GLOBALS["route"]->GET[$Interface];
    if (strpos($GLOBALS["route"]->GET[$Interface], ".json")) {
        $this->Query = substr($this->Query, 0, -5);
    }
}

switch ($Interface) {
    case "v1":
        require_once __DIR__ . '/case/v1.php';
        break;
    default:
        echo \crisp\core\PluginAPI::response(crisp\core\Bitmask::VERSION_NOT_FOUND, "Invalid Version", []);
}