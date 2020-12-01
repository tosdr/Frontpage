<?php

$Services = [];
$EnvFile = parse_ini_file(__DIR__ . "/../../../.env");

foreach (explode(",", $EnvFile["FRONTPAGE_SERVICES"]) as $ID) {
    $Service = \crisp\api\Phoenix::getService($ID);
    array_push($Services, $Service);
}


$_vars = array("PopularServices" => $Services);
