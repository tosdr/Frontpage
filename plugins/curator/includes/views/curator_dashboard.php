<?php

header("X-SKIPCACHE: 1");
if (!isset($_SESSION[\crisp\core\Config::$Cookie_Prefix . "session_login"])) {
    header("Location: /login");
    exit;
}


$User = new crisp\plugin\curator\PhoenixUser($_SESSION[\crisp\core\Config::$Cookie_Prefix . "session_login"]["User"]);

if (!$User->isSessionValid()) {
    header("Location: /login");
    exit;
}