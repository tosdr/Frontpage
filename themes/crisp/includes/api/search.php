<?php

if (empty($this->Query)) {
    foreach (\crisp\api\Config::get("frontpage_services") as $ID) {
        $Array[] = crisp\api\Phoenix::getServicePG($ID);
    }
} else {
    foreach (crisp\api\Phoenix::searchServiceByNamePG(strtolower($this->Query)) as $Service) {
        $Array[] = $Service;
    }
}
$Array = array_slice($Array, 0, 10);
if (count($Array) > 0) {
    $cols = 2;
    if (crisp\api\Helper::isMobile()) {
        $cols = 1;
    }
    echo \crisp\core\PluginAPI::response(false, $this->Query, (array("service" => $Array, "grid" => $this->TwigTheme->render("components/servicegrid/grid.twig", array("Services" => $Array, "columns" => $cols)))));
    exit;
}
echo \crisp\core\PluginAPI::response(false, $this->Query, (array("service" => $Array, "grid" => $this->TwigTheme->render("components/servicegrid/no_service.twig", []))));
