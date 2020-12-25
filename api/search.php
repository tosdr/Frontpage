<?php

define('CRISP_API', true);

require_once __DIR__ . "/../pixelcatproductions/crisp.php";

header("Content-Type: application/json");

$Array;

if (empty($_GET["q"])) {
    foreach (\crisp\api\Config::get("frontpage_services") as $ID) {
        $Array[] = crisp\api\Phoenix::getService($ID);
    }
} else {
    foreach (crisp\api\Phoenix::searchServiceByNamePG(strtolower($_GET["q"])) as $Service) {
        $Array[] = $Service;
    }
}
$Array = array_slice($Array, 0, 10);
if (count($Array) > 0) {
    $cols = 2;
    if (crisp\api\Helper::isMobile()) {
        $cols = 1;
    }
    echo json_encode(array("service" => $Array, "grid" => $TwigTheme->render("components/servicegrid/grid.twig", array("Services" => $Array, "columns" => $cols))));
    exit;
}
echo json_encode(array("service" => $Array, "grid" => $TwigTheme->render("components/servicegrid/no_service.twig", [])));
