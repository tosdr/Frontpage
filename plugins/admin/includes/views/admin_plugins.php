<?php

include __DIR__ . '/../Phoenix.php';

if (!isset($_SESSION[\crisp\core\Config::$Cookie_Prefix . "session_login"])) {
    header("Location: /login");
    exit;
}

$User = new crisp\plugin\admin\PhoenixUser($_SESSION[\crisp\core\Config::$Cookie_Prefix . "session_login"]["User"]);
if (!$User->isSessionValid() || CURRENT_UNIVERSE != crisp\Universe::UNIVERSE_TOSDR) {
    header("Location: /login?oldtoken=" . $_SESSION[\crisp\core\Config::$Cookie_Prefix . "session_login"]["Token"]);
    exit;
}

if (!isset($_SESSION[crisp\core\Config::$Cookie_Prefix . "_csrf"])) {
    $_SESSION[crisp\core\Config::$Cookie_Prefix . "_csrf"] = crisp\core\Crypto::UUIDv4("csrf_");
}


$_vars["loadedPlugins"] = \crisp\core\Plugins::loadedPlugins();
$_vars["availablePlugins"] = \crisp\core\Plugins::listPlugins();


$TwigTheme->addFunction(new \Twig\TwigFunction('getPluginMetadata', [new \crisp\core\Plugins(), 'getPluginMetadata']));
$TwigTheme->addFunction(new \Twig\TwigFunction('isInstalled', [new \crisp\core\Plugins(), 'isInstalled']));
$TwigTheme->addFunction(new \Twig\TwigFunction('testVersion', [new \crisp\core\Plugins(), 'testVersion']));

$_vars["csrf"] = $_SESSION[crisp\core\Config::$Cookie_Prefix . "_csrf"];
