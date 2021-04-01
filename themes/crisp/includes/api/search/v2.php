<?php

$inputQuery = $_GET["query"] ?? $inputQuery;

if (empty($inputQuery) || !isset($inputQuery)) {
    echo \crisp\core\PluginAPI::response(crisp\core\Bitmask::QUERY_FAILED + \crisp\core\Bitmask::VERSION_DEPRECATED, "Empty request", (array(
        "results" => 0,
        "service" => []
    )));
    exit;
}


foreach (crisp\api\Phoenix::searchServiceByNamePG(strtolower($inputQuery)) as $Service) {
    $Array[] = $Service;
}

if (count($Array) > 0) {
    echo \crisp\core\PluginAPI::response(\crisp\core\Bitmask::REQUEST_SUCCESS + \crisp\core\Bitmask::VERSION_DEPRECATED, $inputQuery, (array(
        "results" => count($Array),
        "service" => $Array
    )));
    exit;
}
echo \crisp\core\PluginAPI::response(\crisp\core\Bitmask::REQUEST_SUCCESS + crisp\core\Bitmask::QUERY_FAILED + \crisp\core\Bitmask::VERSION_DEPRECATED, $inputQuery, (array(
    "results" => count($Array),
    "service" => $Array
)));
