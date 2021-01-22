<?php

if (!isset($GLOBALS["route"]->GET["q"])) {
  header("Location: /");
  exit;
}
try {
  if (is_numeric($GLOBALS["route"]->GET["q"])) {
    $_vars = array("service" => \crisp\api\Phoenix::getServicePG($GLOBALS["route"]->GET["q"]));
  } else {
    $_vars = array("service" => \crisp\api\Phoenix::getServiceByNamePG(urldecode($GLOBALS["route"]->GET["q"])));
  }
} catch (\Exception $ex) {
  header("Location: /");
  exit;
}