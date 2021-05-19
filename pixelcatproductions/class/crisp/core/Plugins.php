<?php

/* 
 * Copyright (C) 2021 Justin René Back <justin@tosdr.org>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */


namespace crisp\core;

use crisp\api\Helper;
use crisp\api\lists\Cron;
use crisp\api\lists\Languages;
use crisp\api\Translation;
use crisp\core;
use crisp\exceptions\BitmaskException;
use Exception;
use JetBrains\PhpStorm\NoReturn;
use PDO;
use PDOException;
use stdClass;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use TwigEnvironment;
use function file_exists;
use function file_get_contents;
use function function_exists;
use function hash_file;
use function is_array;
use function is_callable;
use function is_object;
use function realpath;
use function serialize;

/**
 * Used internally, plugin loader
 *
 */
class Plugins
{

    use Hook;

    /**
     * Load API files and check if plugin matches it.
     * @param string $Interface The interface we are listening on
     * @param string $_QUERY The query
     */
    #[NoReturn] public static function loadAPI(string $Interface, string $_QUERY): void
    {
        $DB = new MySQL();
        $DBConnection = $DB->getDBConnector();

        $statement = $DBConnection->prepare("SELECT * FROM loadedPlugins");
        $statement->execute();


        $loadedPlugins = $statement->fetchAll(PDO::FETCH_ASSOC);

        foreach ($loadedPlugins as $Plugin) {
            $PluginFolder = \crisp\api\Config::get("plugin_dir");
            $PluginName = $Plugin["name"];
            if (Helper::isValidPluginName($PluginName)) {
                new PluginAPI($PluginFolder, $PluginName, $Interface, $_QUERY);
            } else {
                PluginAPI::response(Bitmask::INVALID_PLUGIN_NAME, Helper::isValidPluginName($PluginName), $PluginName);
                exit;
            }
        }
        PluginAPI::response(Bitmask::INTERFACE_NOT_FOUND, "API Interface not found", [], null, 404);
        exit;
    }

    /**
     * List all uninstalled plugins
     * @return array
     */
    public static function listPlugins(): array
    {

        $PluginFolder = \crisp\api\Config::get("plugin_dir");

        $Array = [];

        foreach (glob(__DIR__ . "/../../../../$PluginFolder/*", GLOB_ONLYDIR) as $Plugin) {
            if (!Plugins::isInstalled(basename($Plugin)) && Plugins::isValid(basename($Plugin))) {
                $Array[] = array("Name" => basename($Plugin));
            }
        }

        return $Array;
    }

    /**
     * List all installed plugins
     * @return array
     */
    public static function loadedPlugins(): array
    {
        $DB = new MySQL();
        $DBConnection = $DB->getDBConnector();

        $statement = $DBConnection->prepare("SELECT * FROM loadedPlugins");
        $statement->execute();


        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Load all plugins and check for matching templates
     * @param Environment $TwigTheme The twig theme component
     * @param string $CurrentFile The current file, __FILE__
     * @param string $CurrentPage The current page template to render
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws BitmaskException
     * @throws Exception
     */
    public static function load(Environment $TwigTheme, string $CurrentFile, string $CurrentPage)
    {

        if (isset($_GET["simulate_invalid_plugin_name"])) {
            throw new Exception("Plugin <b>debug</b> failed to load due to an invalid plugin name!");
        }


        $DB = new MySQL();
        $DBConnection = $DB->getDBConnector();

        $statement = $DBConnection->prepare("SELECT * FROM loadedPlugins ORDER BY \"order\" DESC");
        $statement->execute();


        $loadedPlugins = $statement->fetchAll(PDO::FETCH_ASSOC);

        foreach ($loadedPlugins as $Plugin) {
            $PluginFolder = \crisp\api\Config::get("plugin_dir");
            $PluginName = $Plugin["name"];

            if (file_exists(__DIR__ . "/../../../../$PluginFolder/$PluginName/plugin.json")) {
                $PluginMetadata = json_decode(file_get_contents(__DIR__ . "/../../../../$PluginFolder/$PluginName/plugin.json"));
                if (is_object($PluginMetadata) && isset($PluginMetadata->hookFile)) {
                    if (Helper::isValidPluginName($PluginName)) {
                        new Plugin($PluginFolder, $PluginName, $PluginMetadata, $TwigTheme, $CurrentFile, $CurrentPage);
                    } else {
                        throw new Exception(sprintf("Plugin <b>%s</b> failed to load due to an invalid plugin name!", $PluginName));
                    }
                } else {
                    if (!is_object($PluginMetadata)) throw new Exception(sprintf("Plugin <b>%s</b> failed to load due to an invalid plugin.json!", $PluginName));
                    if (!isset($PluginMetadata->hookFile)) throw new Exception(sprintf("Plugin <b>%s</b> failed to load due to a missing hook file!", $PluginName));
                }
            }
        }

        if (count($GLOBALS["render"]) > 0) {
            $GLOBALS["microtime"]["logic"]["end"] = microtime(true);
            $TwigTheme->addGlobal("LogicMicroTime", ($GLOBALS["microtime"]["logic"]["end"] - $GLOBALS["microtime"]["logic"]["start"]));
            header("X-CMS-LogicTime: " . ($GLOBALS["microtime"]["logic"]["end"] - $GLOBALS["microtime"]["logic"]["start"]));
        }

        foreach ($GLOBALS["render"] as $Template => $_vars) {
            $GLOBALS["microtime"]["template"]["start"] = microtime(true);
            echo $TwigTheme->render($Template, $_vars);
            //$this->broadcastHook("pluginAfterRender", $Template);
        }
    }

    /**
     * @param string $PluginName
     */
    public static function migrate(string $PluginName): void
    {
        $Migrations = new Migrations();
        $PluginFolder = \crisp\api\Config::get("plugin_dir");
        $Migrations->migrate(__DIR__ . "/../../../../$PluginFolder/$PluginName/", $PluginName);
    }

    /**
     * @param string $PluginName
     * @param stdClass $PluginMetadata
     * @param Environment $TwigTheme
     * @param string $CurrentFile
     * @param string $CurrentPage
     * @return bool
     */
    private static function performOnInstall(string $PluginName, stdClass $PluginMetadata, Environment $TwigTheme, string $CurrentFile, string $CurrentPage): bool
    {
        if (!isset($PluginMetadata->onInstall)) {
            return false;
        }

        self::installKVStorage($PluginName, $PluginMetadata);
        self::installTranslations($PluginName, $PluginMetadata);
        self::installCrons($PluginName, $PluginMetadata);

        if (isset($PluginMetadata->onInstall->activateDependencies) && is_array($PluginMetadata->onInstall->activateDependencies)) {
            foreach ($PluginMetadata->onInstall->activateDependencies as $Plugin) {
                if (!self::isInstalled($PluginName)) {
                    self::install($Plugin, $TwigTheme, $CurrentFile, $CurrentPage);
                }
            }
        }
    }

    /**
     * Reinstall all translations
     * @param string $PluginName The name of the plugin
     * @param string $PluginMetadata plugin.json contents decoded
     * @return bool
     * @deprecated 0.0.8-beta.RC4 Use self::installTranslations
     */
    public static function refreshTranslations($PluginName, $PluginMetadata)
    {
        self::uninstallTranslations($PluginName, $PluginMetadata);
        return self::installTranslations($PluginName, $PluginMetadata);
    }

    /**
     * Reinstall all storage items
     * @param string $PluginName The name of the plugin
     * @param string $PluginMetadata plugin.json contents decoded
     * @return bool
     * @deprecated 0.0.8-beta.RC4 Use self::installKVStorage
     */
    public static function refreshKVStorage($PluginName, $PluginMetadata)
    {
        self::uninstallKVStorage($PluginName, $PluginMetadata);
        return self::installKVStorage($PluginName, $PluginMetadata);
    }

    /**
     * Install storage from plugin.json
     * @param string $PluginName The name of the plugin
     * @param string $PluginMetadata plugin.json contents decoded
     * @return bool
     */
    public static function installKVStorage($PluginName, $PluginMetadata)
    {
        if (!is_object($PluginMetadata) && !isset($PluginMetadata->hookFile)) {
            return false;
        }
        if (isset($PluginMetadata->onInstall->createKVStorageItems) && is_object($PluginMetadata->onInstall->createKVStorageItems)) {

            if (defined("CRISP_CLI")) {
                echo "----------" . PHP_EOL;
                echo "Installing storage for plugin $PluginName" . PHP_EOL;
                echo "----------" . PHP_EOL;
            }
            foreach ($PluginMetadata->onInstall->createKVStorageItems as $Key => $Value) {
                if (is_array($Value) || is_object($Value)) {
                    $Value = serialize($Value);
                }

                try {


                    if (\crisp\api\Config::set("plugin_" . $PluginName . "_$Key", $Value)) {
                        if (defined("CRISP_CLI")) {
                            echo "Installing key $Key" . PHP_EOL;
                        }
                    }
                } catch (PDOException $ex) {
                    if (defined("CRISP_CLI")) {
                        var_dump($ex);
                    }
                    continue;
                }
            }
        }
        return true;
    }

    /**
     * Install all translations from plugin.json
     * @param string $PluginName The name of the plugin
     * @param string $PluginMetadata plugin.json contents decoded
     * @return bool
     */
    public static function installTranslations($PluginName, $PluginMetadata)
    {
        if (!is_object($PluginMetadata) && !isset($PluginMetadata->hookFile)) {
            return false;
        }
        if (defined("CRISP_CLI")) {
            echo "----------" . PHP_EOL;
            echo "Installing translations for plugin $PluginName" . PHP_EOL;
            echo "----------" . PHP_EOL;
        }


        if (isset($PluginMetadata->onInstall->createTranslationKeys) && is_string($PluginMetadata->onInstall->createTranslationKeys)) {

            $PluginFolder = \crisp\api\Config::get("plugin_dir");
            if (file_exists(__DIR__ . "/../../../../$PluginFolder/$PluginName/" . $PluginMetadata->onInstall->createTranslationKeys)) {

                $files = glob(__DIR__ . "/../../../../$PluginFolder/$PluginName/" . $PluginMetadata->onInstall->createTranslationKeys . "*.{json}", GLOB_BRACE);
                foreach ($files as $File) {

                    if (defined("CRISP_CLI")) {
                        echo "----------" . PHP_EOL;
                        echo "Installing language " . substr(basename($File), 0, -5) . PHP_EOL;
                        echo "----------" . PHP_EOL;
                    }
                    if (!file_exists($File)) {
                        continue;
                    }
                    $Language = Languages::getLanguageByCode(substr(basename($File), 0, -5));

                    if (!$Language) {
                        continue;
                    }

                    foreach (json_decode(file_get_contents($File), true) as $Key => $Value) {
                        try {
                            if ($Language->newTranslation("plugin." . $PluginName . ".$Key", $Value, substr(basename($File), 0, -5))) {
                                if (defined("CRISP_CLI")) {
                                    echo "Installing translation $Key" . PHP_EOL;
                                }
                            }
                        } catch (PDOException $ex) {
                            if (defined("CRISP_CLI")) {
                                var_dump($ex);
                            }
                            continue;
                        }
                    }
                }
            }
            return true;
        }

        if (isset($PluginMetadata->onInstall->createTranslationKeys) && is_object($PluginMetadata->onInstall->createTranslationKeys)) {
            foreach ($PluginMetadata->onInstall->createTranslationKeys as $Key => $Value) {

                try {
                    $Language = Languages::getLanguageByCode($Key);

                    if (!$Language) {
                        continue;
                    }

                    foreach ($Value as $KeyTranslation => $ValueTranslation) {
                        $Language->newTranslation("plugin." . $PluginName . ".$KeyTranslation", $ValueTranslation, $Key);
                    }
                } catch (PDOException $ex) {
                    continue;
                }
            }
            return true;
        }
        return false;
    }

    /**
     * Checks if the specified plugin is installed
     * @param string $PluginName The folder name of the plugin
     * @return boolean TRUE if plugin is installed, otherwise FALSE
     */
    public static function isInstalled($PluginName)
    {
        $DB = new MySQL();
        $DBConn = $DB->getDBConnector();

        $statement = $DBConn->prepare("SELECT * FROM loadedPlugins WHERE Name = :Key");
        $statement->execute(array(":Key" => $PluginName));

        return ($statement->rowCount() > 0 ? true : false);
    }

    public static function isValid($PluginName)
    {
        $PluginFolder = \crisp\api\Config::get("plugin_dir");
        return file_exists(__DIR__ . "/../../../../$PluginFolder/$PluginName/plugin.json");
    }

    /**
     * Deletes all KVStorage Items from the Plugin
     *
     * If the plugin is installed, it will get uninstalled first
     * @param string $PluginName The folder name of the plugin
     * @return boolean TRUE if the data has been successfully deleted
     */
    public static function deleteData($PluginName)
    {

        if (self::isInstalled($PluginName)) {
            self::uninstall($PluginName);
        }

        $PluginFolder = \crisp\api\Config::get("plugin_dir");

        if (!file_exists(__DIR__ . "/../../../../$PluginFolder/$PluginName/plugin.json")) {
            return false;
        }

        $PluginMetadata = json_decode(file_get_contents(__DIR__ . "/../../../../$PluginFolder/$PluginName/plugin.json"));

        self::uninstallKVStorage($PluginName, $PluginMetadata);

        self::uninstallTranslations($PluginName, $PluginMetadata);
    }

    /**
     * Compare the current version of crisp with a custom version string
     * @param string $VersionString The semantic version string
     * @return bool|int
     * @see version_compare
     */
    public static function testVersion($VersionString)
    {

        if (strpos($VersionString, ">=") !== false) {
            return version_compare(core::CRISP_VERSION, substr($VersionString, 2), ">=");
        } elseif (strpos($VersionString, "<=") !== false) {
            return version_compare(core::CRISP_VERSION, substr($VersionString, 2), "<=");
        } elseif (strpos($VersionString, "<") !== false) {
            return version_compare(core::CRISP_VERSION, substr($VersionString, 1), "<");
        } elseif (strpos($VersionString, "=") !== false) {
            return version_compare(core::CRISP_VERSION, substr($VersionString, 1), "=");
        } elseif (strpos($VersionString, "!=") !== false) {
            return version_compare(core::CRISP_VERSION, substr($VersionString, 2), "!=");
        } elseif (strpos($VersionString, ">") !== false) {
            return version_compare(core::CRISP_VERSION, substr($VersionString, 1), ">");
        } else {
            return version_compare(core::CRISP_VERSION, $VersionString);
        }
    }

    /**
     * Gets the decoded contents of the plugin.json
     * @param string $PluginName The name of the plugin
     * @return boolean
     */
    public static function getPluginMetadata($PluginName)
    {
        $PluginFolder = \crisp\api\Config::get("plugin_dir");

        if (!file_exists(__DIR__ . "/../../../../$PluginFolder/$PluginName/plugin.json")) {
            return false;
        }

        return json_decode(file_get_contents(__DIR__ . "/../../../../$PluginFolder/$PluginName/plugin.json"));
    }

    /**
     * Uninstall all crons by plugin
     * @param string $PluginName The name of the plugin
     * @param string $PluginMetadata plugin.json contents decoded
     * @return bool
     */
    public static function uninstallCrons($PluginName, $PluginMetadata)
    {
        if (!is_object($PluginMetadata) && !isset($PluginMetadata->hookFile)) {
            return false;
        }

        if (isset($PluginMetadata->onInstall->createCron) && is_array($PluginMetadata->onInstall->createCron)) {
            if (defined("CRISP_CLI")) {
                echo "Removing crons " . $PluginName . PHP_EOL;
            }
            Cron::deleteByPlugin($PluginName);
        }
    }

    /**
     * Install all crons by plugin
     * @param string $PluginName The name of the plugin
     * @param string $PluginMetadata plugin.json contents decoded
     * @return bool
     */
    public static function installCrons($PluginName, $PluginMetadata)
    {
        if (!is_object($PluginMetadata) && !isset($PluginMetadata->hookFile)) {
            return false;
        }

        if (defined("CRISP_CLI")) {
            echo "----------" . PHP_EOL;
            echo "Installing crons for plugin $PluginName" . PHP_EOL;
            echo "----------" . PHP_EOL;
        }
        if (isset($PluginMetadata->onInstall->createCron) && is_array($PluginMetadata->onInstall->createCron)) {

            foreach ($PluginMetadata->onInstall->createCron as $Cron) {

                if (defined("CRISP_CLI")) {
                    echo "Installing cron " . $Cron->type . PHP_EOL;
                }
                Cron::create("execute_plugin_cron", json_encode(array("data" => $Cron->data, "name" => $Cron->type)), $Cron->interval, $PluginName);
            }
        }
    }

    /**
     * uninstall all storage items by plugin
     * @param string $PluginName The name of the plugin
     * @param string $PluginMetadata plugin.json contents decoded
     * @return bool
     */
    public static function uninstallKVStorage($PluginName, $PluginMetadata)
    {
        if (!is_object($PluginMetadata) && !isset($PluginMetadata->hookFile)) {
            return false;
        }

        foreach (self::listConfig($PluginName) as $Key => $Value) {
            if (defined("CRISP_CLI")) {
                echo "Deleting $Key" . PHP_EOL;
            }
            \crisp\api\Config::delete($Key);

            if (defined("CRISP_CLI")) {
                echo "Deleted $Key" . PHP_EOL;
            }
        }

        /*
          if (isset($PluginMetadata->onInstall->createKVStorageItems) && \is_object($PluginMetadata->onInstall->createKVStorageItems)) {
          foreach ($PluginMetadata->onInstall->createKVStorageItems as $Key => $Value) {
          \crisp\api\Config::delete("plugin_" . $PluginName . "_$Key");
          }
          }
         */

        return true;
    }

    /**
     * List all translations by plugin
     * @param string $PluginName The name of the plugin
     * @return array
     */
    public static function listTranslations($PluginName)
    {

        $Configs = Translation::listTranslations();


        foreach ($Configs as $Key => $Translation) {
            if (strpos($Translation["key"], "plugin_$PluginName") === false) {
                unset($Configs[$Key]);
            }
        }
        return $Configs;
    }

    /**
     * List all storage items by plugins
     * @param string $PluginName The name of the plugin
     * @return array
     */
    public static function listConfig($PluginName)
    {

        $Configs = \crisp\api\Config::list();


        foreach ($Configs as $Key => $Value) {
            if (strpos($Key, "plugin_$PluginName") === false) {
                unset($Configs[$Key]);
            }
        }
        return $Configs;
    }

    /**
     * Uninstall all translations by plugin
     * @param string $PluginName The name of the plugin
     * @param string $PluginMetadata plugin.json contents decoded
     * @return bool
     */
    public static function uninstallTranslations($PluginName, $PluginMetadata)
    {
        if (!is_object($PluginMetadata) && !isset($PluginMetadata->hookFile)) {
            return false;
        }
        $Language = Languages::getLanguageByCode("en");

        if (defined("CRISP_CLI")) {
            echo "Deleting translations for " . $PluginName . PHP_EOL;
        }
        foreach (self::listTranslations($PluginName) as $Translation) {
            if (defined("CRISP_CLI")) {
                echo "Deleting translation " . $Translation["key"] . PHP_EOL;
            }
            $Language->deleteTranslation($Translation["key"]);
        }
    }

    private static function performOnUninstall($PluginName, $PluginMetadata)
    {

        if (isset($PluginMetadata->onUninstall->purgeDependencies) && is_array($PluginMetadata->onUninstall->purgeDependencies)) {
            foreach ($PluginMetadata->onUninstall->purgeDependencies as $Plugin) {
                self::deleteData($Plugin);
            }
        } else if (isset($PluginMetadata->onUninstall->deactivateDependencies) && is_array($PluginMetadata->onUninstall->deactivateDependencies)) {
            foreach ($PluginMetadata->onUninstall->deactivateDependencies as $Plugin) {
                self::uninstall($Plugin);
            }
        }
        if ($PluginMetadata->onUninstall->deleteData) {
            self::deleteData($PluginName);
        }
    }

    /**
     * Reinstall a plugin
     * @param string $PluginName The name of the plugin
     * @param TwigEnvironment $TwigTheme The twig theme component
     * @param string $CurrentFile The current file, __FILE__
     * @param string $CurrentPage The current page template to render
     * @return boolean
     */
    public static function reinstall($PluginName, $TwigTheme, $CurrentFile, $CurrentPage)
    {
        if (!self::uninstall($PluginName, $TwigTheme, $CurrentFile, $CurrentPage)) {
            return false;
        }
        return self::install($PluginName, $TwigTheme, $CurrentFile, $CurrentPage);
    }

    /**
     * Uninstall a plugin and prevent it from loading
     * @broadcasts pluginUninstall
     * @param string $PluginName The Folder name of the Plugin
     * @param TwigEnvironment $TwigTheme The twig theme component
     * @param string $CurrentFile The current file, __FILE__
     * @param string $CurrentPage The current page template to render
     * @return bool
     * @see registerUninstallHook
     */
    public static function uninstall($PluginName, $TwigTheme, $CurrentFile, $CurrentPage)
    {
        $DB = new MySQL();
        $DBConn = $DB->getDBConnector();

        $statement = $DBConn->prepare("DELETE FROM loadedPlugins WHERE name = :Key");
        $statement->execute(array(":Key" => $PluginName));

        $PluginFolder = \crisp\api\Config::get("plugin_dir");

        if (!file_exists(__DIR__ . "/../../../../$PluginFolder/$PluginName/plugin.json")) {
            return false;
        }


        $PluginMetadata = json_decode(file_get_contents(__DIR__ . "/../../../../$PluginFolder/$PluginName/plugin.json"));


        if (!is_object($PluginMetadata) && !isset($PluginMetadata->hookFile)) {
            return false;
        }
        self::performOnUninstall($PluginName, $PluginMetadata);

        self::uninstallCrons($PluginName, $PluginMetadata);

        $Migrations = new Migrations();

        $Migrations->deleteByPlugin($PluginName);

        new Plugin($PluginFolder, $PluginName, $PluginMetadata, $TwigTheme, $CurrentFile, $CurrentPage);


        self::broadcastHook("pluginUninstall_$PluginName", null);
        self::broadcastHook("pluginUninstall", $PluginName);
        return true;
    }

    /**
     * Check if the integrity of a plugin is still fine
     * @param string $PluginName The name of the plugin
     * @param string $PluginMetadata Decoded plugin.json contents
     * @return array
     */
    public static function integrityCheck($PluginName, $PluginMetadata)
    {
        $PluginFolder = \crisp\api\Config::get("plugin_dir");
        $parsedConfigs = array();
        $failedConfigs = array();
        $failedChecks = array();
        $parsedChecks = array();
        $parsedFiles = array();
        $failedFiles = array();
        $integrity = true;
        if (isset($PluginMetadata->onInstall->createKVStorageItems) && is_object($PluginMetadata->onInstall->createKVStorageItems)) {
            foreach ($PluginMetadata->onInstall->createKVStorageItems as $Key => $Value) {
                if (is_array($Value) || is_object($Value)) {
                    $Value = serialize($Value);
                }
                try {
                    $KeyExists = \crisp\api\Config::exists("plugin_" . $PluginName . "_$Key");
                    $parsedConfigs[$Key] = $Value;
                    if (!$KeyExists) {
                        $failedConfigs[$Key] = $Value;
                    }
                } catch (PDOException $ex) {
                    $failedConfigs[$Key] = $Value;
                }
            }
            $parsedChecks[] = "KVStorageItems";
            if (count($failedConfigs) > 0) {
                $integrity = false;
                $failedChecks[] = "KVStorageItems";
            }
        }


        if (!self::isInstalled($PluginName)) {
            $integrity = false;
            $failedChecks[] = "isInstalled";
        } else {
            $parsedChecks[] = "isInstalled";
        }

        $PluginPath = realpath(__DIR__ . "/../../../../$PluginFolder" . DIRECTORY_SEPARATOR . "$PluginName") . DIRECTORY_SEPARATOR;
        if (file_exists($PluginPath . "sha256sum.txt")) {

            foreach (file($PluginPath . "sha256sum.txt") as $line) {
                if (!ctype_space($line)) {
                    $line = explode("  ", $line);
                    $Hash = $line[0];
                    $File = trim($line[1]);
                    $HashFile = hash_file("sha256", $PluginPath . $File);
                    $parsedFiles[$File] = $HashFile;
                    if ($HashFile != $Hash) {
                        $failedFiles[$File] = $HashFile;
                    }
                }
            }
            if (count($failedFiles) > 0) {
                $integrity = false;
                $failedChecks[] = "hash_file";
            }
            $parsedChecks[] = "hash_file";
        }

        return array(
            "integrity" => $integrity,
            "parsedConfigs" => $parsedConfigs,
            "failedConfigs" => $failedConfigs,
            "parsedFiles" => $parsedFiles,
            "failedFiles" => $failedFiles,
            "isInstalled" => self::isInstalled($PluginName),
        );
    }

    /**
     * Install a plugin and load it into the CMS
     * @broadcasts pluginInstall
     * @param string $PluginName The Folder name of the Plugin
     * @param TwigEnvironment $TwigTheme The twig theme component
     * @param string $CurrentFile The current file, __FILE__
     * @param string $CurrentPage The current page template to render
     * @return boolean TRUE if install was successful, otherwise FALSE
     * @see registerInstallHook
     */
    public static function install($PluginName, $TwigTheme, $CurrentFile, $CurrentPage)
    {

        $DB = new MySQL();
        $DBConn = $DB->getDBConnector();


        $statement2 = $DBConn->prepare("SELECT * FROM loadedPlugins WHERE Name = :Key");
        $statement2->execute(array(":Key" => $PluginName));

        if ($statement2->rowCount() > 0) {
            return false;
        }

        $PluginFolder = \crisp\api\Config::get("plugin_dir");

        if (!file_exists(__DIR__ . "/../../../../$PluginFolder/$PluginName/plugin.json")) {
            return false;
        }

        $PluginMetadata = json_decode(file_get_contents(__DIR__ . "/../../../../$PluginFolder/$PluginName/plugin.json"));


        if (!is_object($PluginMetadata) && !isset($PluginMetadata->hookFile)) {
            return false;
        }


        self::performOnInstall($PluginName, $PluginMetadata, $TwigTheme, $CurrentFile, $CurrentPage);


        new Plugin($PluginFolder, $PluginName, $PluginMetadata, $TwigTheme, $CurrentFile, $CurrentPage);

        self::broadcastHook("pluginInstall_$PluginName", time());
        self::broadcastHook("pluginInstall", $PluginName);

        self::migrate($PluginName);

        $statement = $DBConn->prepare("INSERT INTO loadedPlugins (Name) VALUES (:Key)");
        return $statement->execute(array(":Key" => $PluginName));
    }

    /**
     * Registers an uninstall hook for your plugin.
     * @param string $PluginName
     * @param string|function $Function Callback function, either anonymous or a string to a function
     * @returns boolean TRUE if hook could be registered, otherwise false
     */
    public static function registerUninstallHook($PluginName, $Function)
    {
        if (is_callable($Function) || function_exists($PluginName)($Function)) {
            self::on("pluginUninstall_$PluginName", $Function);
            return true;
        }
        return false;
    }

    public static function registerAfterRenderHook($PluginName, $Function)
    {
        if (is_callable($Function) || function_exists($PluginName)($Function)) {
            self::on("pluginAfterRender_$PluginName", $Function);
            return true;
        }
        return false;
    }

    /**
     * Registers an install hook for your plugin.
     * @param string $PluginName
     * @param string|function $Function Callback function, either anonymous or a string to a function
     * @returns boolean TRUE if hook could be registered, otherwise false
     */
    public static function registerInstallHook($PluginName, $Function)
    {
        if (is_callable($Function) || function_exists($PluginName)($Function)) {
            self::on("pluginInstall_$PluginName", $Function);
            return true;
        }
        return false;
    }

}
