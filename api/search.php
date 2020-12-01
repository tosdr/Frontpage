<?php

define('CRISP_API', true);

require_once __DIR__ . "/../pixelcatproductions/crisp.php";

header("Content-Type: application/json");

$Redis = new \crisp\core\Redis();

$Redis = $Redis->getDBConnector();

$Array;

if (empty($_GET["q"])) {
    foreach (explode(",", $EnvFile["FRONTPAGE_SERVICES"]) as $ID) {
        $Array[] = crisp\api\Phoenix::getService($ID);
    }
} else {
    foreach ($Redis->keys(\crisp\api\Config::get("phoenix_api_endpoint") . "/services/name/*" . strtolower($_GET["q"]) . "*") as $Key) {
        $Array[] = json_decode($Redis->get($Key));
    }
}
$Array = array_slice($Array, 0, 10);
if (count($Array) > 0) {
    echo json_encode(array("service" => $Array, "grid" => $TwigTheme->render("components/servicegrid/grid.twig", array("Services" => $Array, "columns" => 2))));
    exit;
}
echo json_encode(array("service" => $Array, "grid" => $TwigTheme->render("components/servicegrid/no_service.twig", [])));
