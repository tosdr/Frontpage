<?php

define('NO_KMS', true);

if (php_sapi_name() !== 'cli') {
    echo "Not from CLI";
    exit;
}


error_reporting(error_reporting() & ~E_NOTICE);
require_once __DIR__ . "/../pixelcatproductions/crisp.php";


switch ($argv[1]) {
    case "plugin":
        if ($argc < 3) {
            echo "Missing argument: enable/disable/reinstall" . PHP_EOL;
            exit;
        }

        switch ($argv[2]) {
            case "enable":
                if ($argc < 4) {
                    echo "Missing plugin name" . PHP_EOL;
                    exit;
                }
                if (crisp\core\Plugins::isInstalled($argv[3])) {
                    echo "This plugin is already installed" . PHP_EOL;
                    exit;
                }
                if (!crisp\core\Plugins::isValid($argv[3])) {
                    echo "This plugin does not exist" . PHP_EOL;
                    exit;
                }
                if (crisp\core\Plugins::install($argv[3])) {
                    echo "Plugin successfully installed" . PHP_EOL;
                    exit;
                }
                echo "Failed to install plugin" . PHP_EOL;
                break;
            case "disable":
                if ($argc < 4) {
                    echo "Missing plugin name" . PHP_EOL;
                    exit;
                }
                if (!crisp\core\Plugins::isInstalled($argv[3])) {
                    echo "This plugin is not installed" . PHP_EOL;
                    exit;
                }
                if (!crisp\core\Plugins::isValid($argv[3])) {
                    echo "This plugin does not exist" . PHP_EOL;
                    exit;
                }
                if (crisp\core\Plugins::uninstall($argv[3])) {
                    echo "Plugin successfully uninstalled" . PHP_EOL;
                    exit;
                }
                echo "Failed to uninstall plugin" . PHP_EOL;
                break;
            case "reinstall":
                if ($argc < 4) {
                    echo "Missing plugin name" . PHP_EOL;
                    exit;
                }
                if (!crisp\core\Plugins::isInstalled($argv[3])) {
                    if (!crisp\core\Plugins::install($argv[3])) {
                        echo "Failed to install plugin" . PHP_EOL;
                        exit;
                    }
                    echo "Plugin has been reinstalled" . PHP_EOL;
                    exit;
                }
                if (!crisp\core\Plugins::isValid($argv[3])) {
                    echo "This plugin does not exist" . PHP_EOL;
                    exit;
                }
                if (!crisp\core\Plugins::uninstall($argv[3])) {
                    echo "Failed to uninstall Plugin" . PHP_EOL;
                    exit;
                }
                if (!crisp\core\Plugins::install($argv[3])) {
                    echo "Failed to install plugin" . PHP_EOL;
                    exit;
                }
                echo "Plugin has been reinstalled" . PHP_EOL;
                break;
        }

        break;
    default:
        echo "Invalid command" . PHP_EOL;
}