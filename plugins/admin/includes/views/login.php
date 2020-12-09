<?php

if (isset($_SESSION[\crisp\core\Config::$Cookie_Prefix . "session_login"])) {
    header("Location: /admin_dashboard");
    exit;
}