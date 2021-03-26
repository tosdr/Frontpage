<?php

if (empty($this->Query) || !isset($this->Query)) {
    foreach (\crisp\api\Config::get("frontpage_services") as $ID) {
        $Array[] = crisp\api\Phoenix::getServicePG($ID);
    }
} else {
    foreach (crisp\api\Phoenix::searchServiceByNamePG(strtolower($this->Query)) as $Service) {
        $Array[] = $Service;
    }
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
