<?php

header("X-SKIPCACHE: 1");
if (isset($_SESSION[\crisp\core\Config::$Cookie_Prefix . "session_login"])) {

    $User = new \crisp\plugin\curator\PhoenixUser($_SESSION[\crisp\core\Config::$Cookie_Prefix . "session_login"]["User"]);

    if (!$User->isSessionValid() || CURRENT_UNIVERSE != crisp\Universe::UNIVERSE_TOSDR) {
        unset($_SESSION[\crisp\core\Config::$Cookie_Prefix . "session_login"]);
        header("Location: /login?oldtoken=" . $_SESSION[\crisp\core\Config::$Cookie_Prefix . "session_login"]["Token"]);
        exit;
    }
    header("Location: /curator_dashboard");
    exit;
}
