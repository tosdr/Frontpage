<?php

if (!isset($_GET["q"])) {
    header("Location: /");
    exit;
}
try {
    if (is_numeric($_GET["q"])) {
        $_vars = array("service" => \crisp\api\Phoenix::getService($_GET["q"]));
    } else {
        $_vars = array("service" => \crisp\api\Phoenix::getServiceByName($_GET["q"]));
    }
} catch (\Exception $ex) {
    header("Location: /");
    exit;
}