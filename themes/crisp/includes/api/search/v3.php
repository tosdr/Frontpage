<?php

$inputQuery = $_GET["query"] ?? $inputQuery;
$ES = new \crisp\api\Elastic();

if (!$inputQuery || $inputQuery === null) {
    echo \crisp\core\PluginAPI::response(\crisp\core\Bitmask::QUERY_FAILED, "Missing query", array(
        "services" => array(),
        "grid" => null
    ));
    exit;
}

$services = $ES->search($inputQuery);

echo \crisp\core\PluginAPI::response(\crisp\core\Bitmask::REQUEST_SUCCESS, $inputQuery, array(
    "services" => $services,
    "grid" => $this->TwigTheme->render("components/servicegrid/grid.twig", array("Services" => $services->hits, "columns" => 2))
));

