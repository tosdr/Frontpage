<?php

if (empty($this->Query) || !isset($this->Query)) {
    echo \crisp\core\PluginAPI::response(crisp\core\Bitmask::QUERY_FAILED, "Empty request", (array(
        "results" => 0,
        "service" => []
    )));
    return;
}


foreach (crisp\api\Phoenix::searchServiceByNamePG(strtolower($this->Query)) as $Service) {
    $Array[] = $Service;
}

if (count($Array) > 0) {
    echo \crisp\core\PluginAPI::response(\crisp\core\Bitmask::REQUEST_SUCCESS, $this->Query, (array(
        "results" => count($Array),
        "service" => $Array
    )));
    exit;
}
echo \crisp\core\PluginAPI::response(\crisp\core\Bitmask::REQUEST_SUCCESS + crisp\core\Bitmask::QUERY_FAILED, $this->Query, (array(
    "results" => count($Array),
    "service" => $Array
)));
