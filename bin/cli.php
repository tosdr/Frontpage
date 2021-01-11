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
    case "cache":

        if ($argc < 3) {
            echo "Missing argument: action" . PHP_EOL;
            exit;
        }

        switch ($argv[2]) {
            case "clear":
                \crisp\core\Themes::clearCache();
                echo "Cleared Cache!" . PHP_EOL;
                break;
        }
        break;

    case "plugin":
        if ($argc < 3) {
            echo "Missing argument: enable/disable/reinstall/translations/storage" . PHP_EOL;
            exit;
        }

        switch ($argv[2]) {


            case "storage":

                if ($argc < 4) {
                    echo "Missing argument: reinstall" . PHP_EOL;
                    exit;
                }
                switch ($argv[3]) {
                    case "reinstall":
                    case "refresh":
                        if ($argc < 5) {
                            echo "Missing plugin name" . PHP_EOL;
                            exit;
                        }
                        if (is_array(\crisp\api\Helper::isValidPluginName($argv[4]))) {
                            echo "Invalid Plugin Name:\n" . var_export(\crisp\api\Helper::isValidPluginName($argv[3]), true) . PHP_EOL;
                            exit;
                        }
                        if (!crisp\core\Plugins::isValid($argv[4])) {
                            echo "This plugin does not exist" . PHP_EOL;
                            exit;
                        }
                        if (!crisp\core\Plugins::isInstalled($argv[4])) {
                            echo "This plugin is not installed" . PHP_EOL;
                            exit;
                        }

                        if (\crisp\api\Config::set("plugin_core_maintenance_enabled", true)) {
                            echo "Maintenance Mode successfully enabled." . PHP_EOL;
                        }
                        $Start = microtime(true);
                        if (\crisp\core\Plugins::installKVStorage($argv[4], \crisp\core\Plugins::getPluginMetadata($argv[4]))) {
                            echo "KV Storage refreshed!" . PHP_EOL;
                        } else {
                            echo "Failed to refresh KV Storage" . PHP_EOL;
                        }
                        $End = microtime(true);
                        echo "Took " . \crisp\api\Helper::truncateText($End - $Start, 6, false) . "ms" . PHP_EOL;
                        if (\crisp\api\Config::set("plugin_core_maintenance_enabled", false)) {
                            echo "Maintenance Mode successfully disabled." . PHP_EOL;
                        }
                        break;
                }
                break;

            case "translations":

                if ($argc < 4) {
                    echo "Missing argument: reinstall" . PHP_EOL;
                    exit;
                }
                switch ($argv[3]) {
                    case "reinstall":
                    case "refresh":
                        if ($argc < 5) {
                            echo "Missing plugin name" . PHP_EOL;
                            exit;
                        }
                        if (is_array(\crisp\api\Helper::isValidPluginName($argv[4]))) {
                            echo "Invalid Plugin Name:\n" . var_export(\crisp\api\Helper::isValidPluginName($argv[3]), true) . PHP_EOL;
                            exit;
                        }
                        if (!crisp\core\Plugins::isValid($argv[4])) {
                            echo "This plugin does not exist" . PHP_EOL;
                            exit;
                        }
                        if (!crisp\core\Plugins::isInstalled($argv[4])) {
                            echo "This plugin is not installed" . PHP_EOL;
                            exit;
                        }

                        if (\crisp\api\Config::set("plugin_core_maintenance_enabled", true)) {
                            echo "Maintenance Mode successfully enabled." . PHP_EOL;
                        }
                        $Start = microtime(true);
                        if (\crisp\core\Plugins::installTranslations($argv[4], \crisp\core\Plugins::getPluginMetadata($argv[4]))) {
                            echo "Translations refreshed!" . PHP_EOL;
                        } else {
                            echo "Failed to refresh translations" . PHP_EOL;
                        }
                        $End = microtime(true);
                        echo "Took " . \crisp\api\Helper::truncateText($End - $Start, 6, false) . "ms" . PHP_EOL;
                        if (\crisp\api\Config::set("plugin_core_maintenance_enabled", false)) {
                            echo "Maintenance Mode successfully disabled." . PHP_EOL;
                        }
                        break;
                }
                break;
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

                if (\crisp\api\Config::set("plugin_core_maintenance_enabled", true)) {
                    echo "Maintenance Mode successfully enabled." . PHP_EOL;
                }
                if (crisp\core\Plugins::install($argv[3], \crisp\api\Config::get("theme"), __FILE__, "cli")) {
                    echo "Plugin successfully installed" . PHP_EOL;
                } else {
                    echo "Failed to install plugin" . PHP_EOL;
                }

                if (\crisp\api\Config::set("plugin_core_maintenance_enabled", false)) {
                    echo "Maintenance Mode successfully disabled." . PHP_EOL;
                }
                break;
            case "migrate":
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

                if (\crisp\api\Config::set("plugin_core_maintenance_enabled", true)) {
                    echo "Maintenance Mode successfully enabled." . PHP_EOL;
                }
                $Start = microtime(true);
                crisp\core\Plugins::migrate($argv[3]);

                $End = microtime(true);
                echo "Took " . \crisp\api\Helper::truncateText($End - $Start, 6, false) . "ms" . PHP_EOL;
                if (\crisp\api\Config::set("plugin_core_maintenance_enabled", false)) {
                    echo "Maintenance Mode successfully disabled." . PHP_EOL;
                }
                break;

            case "reload":
            case "refresh":
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
                if (\crisp\api\Config::set("plugin_core_maintenance_enabled", true)) {
                    echo "Maintenance Mode successfully enabled." . PHP_EOL;
                }

                crisp\core\Plugins::installKVStorage($argv[3], \crisp\core\Plugins::getPluginMetadata($argv[3]));
                crisp\core\Plugins::installTranslations($argv[3], \crisp\core\Plugins::getPluginMetadata($argv[3]));

                if (\crisp\api\Config::set("plugin_core_maintenance_enabled", false)) {
                    echo "Maintenance Mode successfully disabled." . PHP_EOL;
                }
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
                if (\crisp\api\Config::set("plugin_core_maintenance_enabled", true)) {
                    echo "Maintenance Mode successfully enabled." . PHP_EOL;
                }
                if (crisp\core\Plugins::uninstall($argv[3], \crisp\api\Config::get("theme"), __FILE__, "cli")) {
                    echo "Plugin successfully uninstalled" . PHP_EOL;
                } else {
                    echo "Failed to uninstall plugin" . PHP_EOL;
                }

                if (\crisp\api\Config::set("plugin_core_maintenance_enabled", false)) {
                    echo "Maintenance Mode successfully disabled." . PHP_EOL;
                }
            case "reinstall":
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

                if (\crisp\api\Config::set("plugin_core_maintenance_enabled", true)) {
                    echo "Maintenance Mode successfully enabled." . PHP_EOL;
                }
                if (crisp\core\Plugins::reinstall($argv[3], \crisp\api\Config::get("theme"), __FILE__, "cli")) {
                    echo "Plugin successfully reinstalled" . PHP_EOL;
                } else {
                    echo "Failed to reinstall plugin" . PHP_EOL;
                }

                if (\crisp\api\Config::set("plugin_core_maintenance_enabled", false)) {
                    echo "Maintenance Mode successfully disabled." . PHP_EOL;
                }
                break;
            case "create_migration":
                if ($argc < 4) {
                    echo "Missing argument: plugin name" . PHP_EOL;
                    exit;
                }
                if ($argc < 5) {
                    echo "Missing argument: migration name" . PHP_EOL;
                    exit;
                }
                \crisp\core\Migrations::create($argv[4], __DIR__ . "/../" . \crisp\api\Config::get("plugin_dir") . "/" . $argv[3]);
                break;
        }
        break;

    case "theme":
        if ($argc < 3) {
            echo "Missing argument: enable/disable/reinstall/translations" . PHP_EOL;
            exit;
        }

        switch ($argv[2]) {



            case "reload":
            case "refresh":
                if ($argc < 4) {
                    echo "Missing theme name" . PHP_EOL;
                    exit;
                }
                if (!crisp\core\Themes::isInstalled($argv[3])) {
                    echo "This theme is not installed" . PHP_EOL;
                    exit;
                }
                if (!crisp\core\Themes::isValid($argv[3])) {
                    echo "This theme does not exist" . PHP_EOL;
                    exit;
                }
                if (\crisp\api\Config::set("plugin_core_maintenance_enabled", true)) {
                    echo "Maintenance Mode successfully enabled." . PHP_EOL;
                }

                crisp\core\Themes::installKVStorage($argv[3], \crisp\core\Themes::getThemeMetadata($argv[3]));
                crisp\core\Themes::installTranslations($argv[3], \crisp\core\Themes::getThemeMetadata($argv[3]));

                if (\crisp\api\Config::set("plugin_core_maintenance_enabled", false)) {
                    echo "Maintenance Mode successfully disabled." . PHP_EOL;
                }
                break;

            case "storage":

                if ($argc < 4) {
                    echo "Missing argument: reinstall" . PHP_EOL;
                    exit;
                }
                switch ($argv[3]) {
                    case "reinstall":
                    case "refresh":
                        if ($argc < 5) {
                            echo "Missing theme name" . PHP_EOL;
                            exit;
                        }
                        if (is_array(\crisp\api\Helper::isValidPluginName($argv[4]))) {
                            echo "Invalid Theme Name:\n" . var_export(\crisp\api\Helper::isValidPluginName($argv[3]), true) . PHP_EOL;
                            exit;
                        }
                        if (!crisp\core\Themes::isValid($argv[4])) {
                            echo "This theme does not exist" . PHP_EOL;
                            exit;
                        }
                        if (!crisp\core\Themes::isInstalled($argv[4])) {
                            echo "This theme is not installed" . PHP_EOL;
                            exit;
                        }
                        if (\crisp\api\Config::set("plugin_core_maintenance_enabled", true)) {
                            echo "Maintenance Mode successfully enabled." . PHP_EOL;
                        }
                        $Start = microtime(true);
                        if (\crisp\core\Themes::installKVStorage(\crisp\core\Themes::getThemeMetadata($argv[4]))) {
                            echo "KV Storage refreshed!" . PHP_EOL;
                        } else {
                            echo "Failed to refresh KV Storage" . PHP_EOL;
                        }
                        $End = microtime(true);
                        echo "Took " . \crisp\api\Helper::truncateText($End - $Start, 6, false) . "ms" . PHP_EOL;

                        if (\crisp\api\Config::set("plugin_core_maintenance_enabled", false)) {
                            echo "Maintenance Mode successfully disabled." . PHP_EOL;
                        }
                        break;
                }
                break;
            case "translations":

                if ($argc < 4) {
                    echo "Missing argument: reinstall" . PHP_EOL;
                    exit;
                }
                switch ($argv[3]) {
                    case "reinstall":
                    case "refresh":
                        if ($argc < 5) {
                            echo "Missing theme name" . PHP_EOL;
                            exit;
                        }
                        if (is_array(\crisp\api\Helper::isValidPluginName($argv[4]))) {
                            echo "Invalid Theme Name:\n" . var_export(\crisp\api\Helper::isValidPluginName($argv[3]), true) . PHP_EOL;
                            exit;
                        }
                        if (!crisp\core\Themes::isValid($argv[4])) {
                            echo "This theme does not exist" . PHP_EOL;
                            exit;
                        }
                        if (!crisp\core\Themes::isInstalled($argv[4])) {
                            echo "This theme is not installed" . PHP_EOL;
                            exit;
                        }
                        if (\crisp\api\Config::set("plugin_core_maintenance_enabled", true)) {
                            echo "Maintenance Mode successfully enabled." . PHP_EOL;
                        }
                        $Start = microtime(true);
                        if (\crisp\core\Themes::installTranslations($argv[4], \crisp\core\Themes::getThemeMetadata($argv[4]))) {
                            echo "Translations refreshed!" . PHP_EOL;
                        } else {
                            echo "Failed to refresh translations" . PHP_EOL;
                        }
                        $End = microtime(true);
                        echo "Took " . \crisp\api\Helper::truncateText($End - $Start, 6, false) . "ms" . PHP_EOL;
                        if (\crisp\api\Config::set("plugin_core_maintenance_enabled", false)) {
                            echo "Maintenance Mode successfully disabled." . PHP_EOL;
                        }
                        break;
                }
                break;
            case "add":
            case "install":
            case "enable":
                if ($argc < 4) {
                    echo "Missing theme name" . PHP_EOL;
                    exit;
                }
                if (is_array(\crisp\api\Helper::isValidPluginName($argv[3]))) {
                    echo "Invalid Theme Name:\n" . var_export(\crisp\api\Helper::isValidPluginName($argv[3]), true) . PHP_EOL;
                    exit;
                }

                if (crisp\core\Themes::isInstalled($argv[3])) {
                    echo "This theme is already installed" . PHP_EOL;
                    exit;
                }
                if (!crisp\core\Themes::isValid($argv[3])) {
                    echo "This theme does not exist" . PHP_EOL;
                    exit;
                }
                if (\crisp\api\Config::set("plugin_core_maintenance_enabled", true)) {
                    echo "Maintenance Mode successfully enabled." . PHP_EOL;
                }
                if (crisp\core\Themes::install($argv[3])) {
                    echo "Theme successfully installed" . PHP_EOL;
                } else {
                    echo "Failed to install theme" . PHP_EOL;
                }
                if (\crisp\api\Config::set("plugin_core_maintenance_enabled", false)) {
                    echo "Maintenance Mode successfully disabled." . PHP_EOL;
                }
                break;
            case "uninstall":
            case "remove":
            case "delete":
            case "disable":
                if ($argc < 4) {
                    echo "Missing theme name" . PHP_EOL;
                    exit;
                }
                if (!crisp\core\Themes::isInstalled($argv[3])) {
                    echo "This theme is not installed" . PHP_EOL;
                    exit;
                }
                if (!crisp\core\Themes::isValid($argv[3])) {
                    echo "This theme does not exist" . PHP_EOL;
                    exit;
                }
                if (crisp\core\Themes::uninstall($argv[3])) {
                    echo "Theme successfully uninstalled" . PHP_EOL;
                    exit;
                }
                echo "Failed to uninstall theme" . PHP_EOL;
                break;
            case "reinstall":
                if ($argc < 4) {
                    echo "Missing theme name" . PHP_EOL;
                    exit;
                }
                if (!crisp\core\Themes::isInstalled($argv[3])) {
                    echo "This theme is not installed" . PHP_EOL;
                    exit;
                }
                if (!crisp\core\Themes::isValid($argv[3])) {
                    echo "This theme does not exist" . PHP_EOL;
                    exit;
                }
                if (\crisp\api\Config::set("plugin_core_maintenance_enabled", true)) {
                    echo "Maintenance Mode successfully enabled." . PHP_EOL;
                }
                if (crisp\core\Themes::reinstall($argv[3], \crisp\api\Config::get("theme"), __FILE__, "cli")) {
                    echo "Theme successfully reinstalled" . PHP_EOL;
                } else {
                    echo "Failed to reinstall theme" . PHP_EOL;
                }
                if (\crisp\api\Config::set("plugin_core_maintenance_enabled", false)) {
                    echo "Maintenance Mode successfully disabled." . PHP_EOL;
                }
                break;
        }
        break;
    case "maintenance":

        if (!\crisp\core\Plugins::isInstalled("core")) {
            echo "Core plugin is not installed!";
            exit;
        }

        if ($argc < 3) {
            echo "Missing argument: enable/disable" . PHP_EOL;
        }

        switch ($argv[2]) {
            case "enable":
            case "on":
            case "true":
                if (\crisp\api\Config::set("plugin_core_maintenance_enabled", true)) {
                    echo "Maintenance Mode successfully enabled." . PHP_EOL;
                    exit;
                }
                echo "Failed to enable maintenance mode" . PHP_EOL;
                break;
            case "false":
            case "disable":
            case "off":
                if (\crisp\api\Config::set("plugin_core_maintenance_enabled", false)) {
                    echo "Maintenance Mode successfully disabled." . PHP_EOL;
                    exit;
                }
                echo "Failed to disable maintenance mode" . PHP_EOL;
                break;
            default:
                if (\crisp\api\Config::get("plugin_core_maintenance_enabled")) {
                    echo "Maintenance Mode is currently enabled!" . PHP_EOL;
                } else {
                    echo "Maintenance Mode is currently disabled." . PHP_EOL;
                }
                break;
        }
        break;
    case "create_migration":


        if ($argc < 3) {
            echo "Missing argument: migration name" . PHP_EOL;
            exit;
        }
        \crisp\core\Migrations::create($argv[2]);
        break;
    case "migrate":
        $Migrations = new crisp\core\Migrations();
        $Migrations->migrate();

        $PluginMigrations = new crisp\core\Plugins();
        foreach (\crisp\core\Plugins::loadedPlugins() as $PluginName) {
            $PluginMigrations->migrate($PluginName["Name"]);
        }
        break;
    default:
        echo "Crisp CLI" . PHP_EOL;
        echo "---------" . PHP_EOL;
        echo "create_migration - Create a new migration file" . PHP_EOL;
        echo "migrate - Migrate MySQL Tables" . PHP_EOL;
        echo "---------" . PHP_EOL;
        echo "cache - Actions regarding the cache" . PHP_EOL;
        echo "cache clear - Clear twig cache" . PHP_EOL;
        echo "---------" . PHP_EOL;
        echo "export - Export various stuff as json to stdout" . PHP_EOL;
        echo "export translations - Export all translations" . PHP_EOL;
        echo "export translations {LanguageKey} - Export all translations by specific language" . PHP_EOL;
        echo "---------" . PHP_EOL;
        echo "import - Import various stuff from files" . PHP_EOL;
        echo "import translations {File} - Import all translations from file" . PHP_EOL;
        echo "---------" . PHP_EOL;
        echo "plugin - Manage plugins on Crisp" . PHP_EOL;
        echo "plugin create_migration {PluginName} {MigrationName} - Create a new migration file" . PHP_EOL;
        echo "plugin enable {PluginName} - Enable a specific plugin" . PHP_EOL;
        echo "plugin add {PluginName} - Enable a specific plugin" . PHP_EOL;
        echo "plugin install {PluginName} - Enable a specific plugin" . PHP_EOL;
        echo "plugin disable {PluginName} - Disable a specific plugin" . PHP_EOL;
        echo "plugin delete {PluginName} - Disable a specific plugin" . PHP_EOL;
        echo "plugin remove {PluginName} - Disable a specific plugin" . PHP_EOL;
        echo "plugin uninstall {PluginName} - Disable a specific plugin" . PHP_EOL;
        echo "plugin storage - Interact with the kv storage of a plugin" . PHP_EOL;
        echo "plugin storage reinstall {PluginName} - Reinstall the KV Storage of a plugin" . PHP_EOL;
        echo "plugin refresh {PluginName} - Refresh a plugin without uninstalling it" . PHP_EOL;
        echo "plugin storage refresh {PluginName} - Reinstall the KV Storage of a plugin" . PHP_EOL;
        echo "plugin translations - Interact with the translations of a plugin" . PHP_EOL;
        echo "plugin translations reinstall {PluginName} - Reinstall the translations of a plugin" . PHP_EOL;
        echo "plugin translations refresh {PluginName} - Reinstall the translations of a plugin" . PHP_EOL;
        echo "---------" . PHP_EOL;
        echo "theme - Manage themes on Crisp" . PHP_EOL;
        echo "theme enable {ThemeName} - Enable a specific theme" . PHP_EOL;
        echo "theme add {ThemeName} - Enable a specific theme" . PHP_EOL;
        echo "theme install {ThemeName} - Enable a specific theme" . PHP_EOL;
        echo "theme disable {ThemeName} - Disable a specific theme" . PHP_EOL;
        echo "theme delete {ThemeName} - Disable a specific theme" . PHP_EOL;
        echo "theme remove {ThemeName} - Disable a specific theme" . PHP_EOL;
        echo "theme uninstall {ThemeName} - Disable a specific theme" . PHP_EOL;
        echo "theme storage - Interact with the kv storage of a theme" . PHP_EOL;
        echo "theme storage reinstall {ThemeName} - Reinstall the KV Storage of a theme" . PHP_EOL;
        echo "theme storage refresh {ThemeName} - Reinstall the KV Storage of a theme" . PHP_EOL;
        echo "theme translations - Interact with the translations of a theme" . PHP_EOL;
        echo "theme refresh {ThemeName} - Refresh a theme without uninstalling it" . PHP_EOL;
        echo "theme translations reinstall {ThemeName} - Reinstall the translations of a theme" . PHP_EOL;
        echo "theme translations refresh {ThemeName} - Reinstall the translations of a theme" . PHP_EOL;
        if (\crisp\core\Plugins::isInstalled("core")) {

            echo "---------" . PHP_EOL;
            echo "maintenance - Manage maintenance mode on crisp" . PHP_EOL;
            echo "maintenance enable - Enable the maintenance mode" . PHP_EOL;
            echo "maintenance on - Enable the maintenance mode" . PHP_EOL;
            echo "maintenance disable - Enable the maintenance mode" . PHP_EOL;
            echo "maintenance off - Enable the maintenance mode" . PHP_EOL;
            echo "maintenance status - Get the status of the maintenance mode" . PHP_EOL;
        }
}