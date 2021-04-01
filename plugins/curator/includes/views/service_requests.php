<?php

header("X-SKIPCACHE: skip");

if (!isset($_SESSION[\crisp\core\Config::$Cookie_Prefix . "session_login"])) {
    header("Location: /login");
    exit;
}

$User = new crisp\plugin\curator\PhoenixUser($_SESSION[\crisp\core\Config::$Cookie_Prefix . "session_login"]["User"]);

if (!$User->isSessionValid() || CURRENT_UNIVERSE != crisp\Universe::UNIVERSE_TOSDR) {
    header("Location: /login");
    exit;
}

$Mysql = new \crisp\core\MySQL();

$requests = $Mysql->getDBConnector()->query("SELECT * FROM service_requests ORDER BY id ASC;");

$_vars = array("requests" => $requests);
