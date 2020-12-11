<?php

if (!isset($_GET["q"])) {
    header("Location: /");
    exit;
}
try {
    if (is_numeric($_GET["q"])) {
        $_vars = array("service" => \crisp\api\Phoenix::getServicePG($_GET["q"]));
    } else {
        $_vars = array("service" => \crisp\api\Phoenix::getServiceByNamePG($_GET["q"]));
    }
} catch (\Exception $ex) {
    header("Location: /");
    exit;
}