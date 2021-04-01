<?php

if (isset($_POST["payload"]) || !empty($_POST["payload"])) {
    $payload = $_POST["payload"];

    if (!isset($payload["name"]) || empty($payload["name"])) {
        echo \crisp\core\PluginAPI::response(crisp\core\Bitmask::MISSING_PARAMETER, "name", []);
        exit;
    }

    if (!isset($payload["documents"]) || empty($payload["documents"])) {
        echo \crisp\core\PluginAPI::response(crisp\core\Bitmask::MISSING_PARAMETER, "documents", []);
        exit;
    }


    if (!is_array($payload["documents"])) {
        echo \crisp\core\PluginAPI::response(crisp\core\Bitmask::INVALID_PARAMETER, "documents is not an array", []);
        exit;
    }

    if (count($payload["documents"]) === 0) {
        echo \crisp\core\PluginAPI::response(crisp\core\Bitmask::INVALID_PARAMETER, "documents is an empty array", []);
        exit;
    }

    foreach ($payload["documents"] as $key => $document) {
        if (!isset($document["name"]) || empty($document["name"])) {
            echo \crisp\core\PluginAPI::response(crisp\core\Bitmask::INVALID_PARAMETER, "documents[$key] is missing the name key", []);
            exit;
        }

        if (!isset($document["url"]) || empty($document["url"])) {
            echo \crisp\core\PluginAPI::response(crisp\core\Bitmask::INVALID_PARAMETER, "documents[$key] is missing the url key", []);
            exit;
        }

        // Validations start here //

        if (!preg_match('/^\b(https?):\/\/[\-A-Za-z0-9+&@#\/%?=~_|!:,.;]*[\-A-Za-z0-9+&@#\/%=~_|]\.+[a-z0-9][a-z0-9-]{0,61}[a-z0-9]\/.*$/', $document["url"])) {
            echo \crisp\core\PluginAPI::response(crisp\core\Bitmask::INVALID_PARAMETER, "documents[$key].url is not conform.", []);
            exit;
        }

        // Validations end here //
    }

    if (!\crisp\api\Phoenix::getServiceByNamePG($payload["name"])) {
        echo \crisp\core\PluginAPI::response(crisp\core\Bitmask::INVALID_PARAMETER, "documents[$key].url is not conform.", []);
        exit;
    }


    exit;
}