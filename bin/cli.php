<?php

define('CRISP_CLI', true);
define('CRISP_API', true);
define('NO_KMS', true);

if (php_sapi_name() !== 'cli') {
    echo "Not from CLI";
    exit;
}


error_reporting(error_reporting() & ~E_NOTICE);
require_once __DIR__ . "/../pixelcatproductions/crisp.php";


switch ($argv[1]) {
    case "export":
        if ($argc < 3) {
            echo "Missing argument: translations" . PHP_EOL;
            exit;
        }
        switch ($argv[2]) {
            case "translations":
                if ($argc < 3) {
                    echo json_encode(\crisp\api\Translation::fetchAll(), JSON_PRETTY_PRINT);
                    exit;
                }

                echo json_encode(\crisp\api\Translation::fetchAllByKey($argv[3]), JSON_PRETTY_PRINT);
                break;
        }
        break;
    case "import":
        if ($argc < 3) {
            echo "Missing argument: translations" . PHP_EOL;
            exit;
        }
        switch ($argv[2]) {
            case "translations":

                if ($argc < 4) {
                    echo "Missing file to import" . PHP_EOL;
                    exit;
                }
                $Json = json_decode(file_get_contents($argv[3]));

                foreach ($Json as $Key => $Value) {

                    try {
                        $Language = \crisp\api\lists\Languages::getLanguageByCode($Key);

                        if (!$Language) {
                            continue;
                        }

                        foreach ($Value as $KeyTranslation => $ValueTranslation) {
                            $Language->newTranslation($KeyTranslation, $ValueTranslation);
                        }
                    } catch (\PDOException $ex) {
                        continue;
                    }
                }
                break;
        }
        break;


    case "plugin":
        if ($argc < 3) {
            echo "Missing argument: enable/disable/reinstall" . PHP_EOL;
            exit;
        }

        switch ($argv[2]) {
            case "add":
            case "install":
            case "enable":
                if ($argc < 4) {
                    echo "Missing plugin name" . PHP_EOL;
                    exit;
                }
                if (is_array(\crisp\api\Helper::isValidPluginName($argv[3]))) {
                    echo "Invalid Plugin Name:\n" . var_export(\crisp\api\Helper::isValidPluginName($argv[3]), true) . PHP_EOL;
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
                if (crisp\core\Plugins::install($argv[3], \crisp\api\Config::get("theme"), __FILE__, "cli")) {
                    echo "Plugin successfully installed" . PHP_EOL;
                    exit;
                }
                echo "Failed to install plugin" . PHP_EOL;
                break;
            case "uninstall":
            case "remove":
            case "delete":
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
                if (crisp\core\Plugins::uninstall($argv[3], \crisp\api\Config::get("theme"), __FILE__, "cli")) {
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
                    if (!crisp\core\Plugins::install($argv[3], \crisp\api\Config::get("theme"), __FILE__, "cli")) {
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
                if (!crisp\core\Plugins::uninstall($argv[3], \crisp\api\Config::get("theme"), __FILE__, "cli")) {
                    echo "Failed to uninstall Plugin" . PHP_EOL;
                    exit;
                }
                if (!crisp\core\Plugins::install($argv[3], \crisp\api\Config::get("theme"), __FILE__, "cli")) {
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