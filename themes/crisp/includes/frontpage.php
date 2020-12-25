<?php

$Services = [];
$EnvFile = parse_ini_file(__DIR__ . "/../../../.env");

if (!isset($_GET["search"])) {
    foreach (\crisp\api\Config::get("frontpage_services") as $ID) {
        $Service = \crisp\api\Phoenix::getServicePG($ID);
        array_push($Services, $Service);
    }
}else{
    $Services = crisp\api\Phoenix::searchServiceByNamePG(strtolower($_GET["search"]));
}


$_vars = array("PopularServices" => $Services);
