<?php

/*
 * Copyright 2020 Pixelcat Productions <support@pixelcatproductions.net>
 * @author 2020 Justin René Back <jback@pixelcatproductions.net>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace crisp\core;

/**
 * Used internally, plugin loader
 *
 */
class Plugins {

    use \crisp\core\Hook;

    public static function load($TwigTheme, $CurrentFile, $CurrentPage) {
        $DB = new \crisp\core\MySQL();
        $DBConnection = $DB->getDBConnector();

        $statement = $DBConnection->prepare("SELECT * FROM loadedPlugins");
        $statement->execute();


        $loadedPlugins = $statement->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($loadedPlugins as $Plugin) {
            $PluginFolder = \crisp\api\Config::get("plugin_dir");
            $PluginName = $Plugin["Name"];

            if (\file_exists(__DIR__ . "/../../../../$PluginFolder/$PluginName/plugin.json")) {
                $PluginMetadata = json_decode(\file_get_contents(__DIR__ . "/../../../../$PluginFolder/$PluginName/plugin.json"));
                if (\is_object($PluginMetadata) && isset($PluginMetadata->hookFile)) {
                    new \crisp\core\Plugin($PluginFolder, $PluginName, $PluginMetadata, $TwigTheme, $CurrentFile, $CurrentPage);
                }
            }
        }
    }

    private static function performOnInstall($PluginName, $PluginMetadata) {
        if (!isset($PluginMetadata->onInstall)) {
            return false;
        }
        if (isset($PluginMetadata->onInstall->createKVStorageItems) && \is_object($PluginMetadata->onInstall->createKVStorageItems)) {
            foreach ($PluginMetadata->onInstall->createKVStorageItems as $Key => $Value) {
                if (is_array($Value) || \is_object($Value)) {
                    $Value = \serialize($Value);
                }
                try {
                    \crisp\api\Config::create("plugin_" . $PluginName . "_$Key", $Value);
                } catch (\PDOException $ex) {
                    continue;
                }
            }
        }

        if (isset($PluginMetadata->onInstall->activateDependencies) && \is_array($PluginMetadata->onInstall->activateDependencies)) {
            foreach ($PluginMetadata->onInstall->activateDependencies as $Plugin) {
                self::install($Plugin);
            }
        }
    }

    /**
     * Checks if the specified plugin is installed
     * @param string $PluginName The folder name of the plugin
     * @return boolean TRUE if plugin is installed, otherwise FALSE
     */
    public static function isInstalled($PluginName) {
        $DB = new \crisp\core\MySQL();
        $DBConn = $DB->getDBConnector();

        $statement = $DBConn->prepare("SELECT * FROM loadedPlugins WHERE `Name` = :Key");
        $statement->execute(array(":Key" => $PluginName));

        return ($statement->rowCount() > 0 ? true : false);
    }

    /**
     * Deletes all KVStorage Items from the Plugin
     * 
     * If the plugin is installed, it will get uninstalled first
     * @param string $PluginName The folder name of the plugin
     * @return boolean TRUE if the data has been successfully deleted
     */
    public static function deleteData($PluginName) {

        if (self::isInstalled($PluginName)) {
            self::uninstall($PluginName);
        }

        $PluginFolder = \crisp\api\Config::get("plugin_dir");

        if (!\file_exists(__DIR__ . "/../../../../$PluginFolder/$PluginName/plugin.json")) {
            return false;
        }

        $PluginMetadata = json_decode(\file_get_contents(__DIR__ . "/../../../../$PluginFolder/$PluginName/plugin.json"));

        if (!\is_object($PluginMetadata) && !isset($PluginMetadata->hookFile)) {
            return false;
        }

        if (isset($PluginMetadata->onInstall->createKVStorageItems) && \is_object($PluginMetadata->onInstall->createKVStorageItems)) {
            foreach ($PluginMetadata->onInstall->createKVStorageItems as $Key => $Value) {
                self::deleteConfig($Key);
            }
        }
    }

    private static function performOnUninstall($PluginName, $PluginMetadata) {

        if (isset($PluginMetadata->onUninstall->purgeDependencies) && \is_array($PluginMetadata->onUninstall->purgeDependencies)) {
            foreach ($PluginMetadata->onUninstall->purgeDependencies as $Plugin) {
                self::deleteData($Plugin);
            }
        } else if (isset($PluginMetadata->onUninstall->deactivateDependencies) && \is_array($PluginMetadata->onUninstall->deactivateDependencies)) {
            foreach ($PluginMetadata->onUninstall->deactivateDependencies as $Plugin) {
                self::uninstall($Plugin);
            }
        }
        if ($PluginMetadata->onUninstall->deleteData) {
            self::deleteData($PluginName);
        }
    }

    /**
     * Uninstall a plugin and prevent it from loading
     * @broadcasts pluginUninstall
     * @see registerUninstallHook
     * @param string $PluginName The Folder name of the Plugin
     */
    public static function uninstall($PluginName) {
        $DB = new \crisp\core\MySQL();
        $DBConn = $DB->getDBConnector();

        $statement = $DBConn->prepare("DELETE FROM loadedPlugins WHERE `Name` = :Key");
        $statement->execute(array(":Key" => $PluginName));

        $PluginFolder = \crisp\api\Config::get("plugin_dir");

        if (!\file_exists(__DIR__ . "/../../../../$PluginFolder/$PluginName/plugin.json")) {
            return false;
        }



        $PluginMetadata = json_decode(\file_get_contents(__DIR__ . "/../../../../$PluginFolder/$PluginName/plugin.json"));


        if (!\is_object($PluginMetadata) && !isset($PluginMetadata->hookFile)) {
            return false;
        }
        self::performOnUninstall($PluginName, $PluginMetadata);

        self::broadcastHook("pluginUninstall_$PluginName", null);
        self::broadcastHook("pluginUninstall", $PluginName);
    }

    public static function integrityCheck($PluginName, $PluginMetadata, $PluginFolder) {
        $parsedConfigs = array();
        $failedConfigs = array();
        $failedChecks = array();
        $parsedChecks = array();
        $parsedFiles = array();
        $failedFiles = array();
        $integrity = true;
        if (isset($PluginMetadata->onInstall->createKVStorageItems) && \is_object($PluginMetadata->onInstall->createKVStorageItems)) {
            foreach ($PluginMetadata->onInstall->createKVStorageItems as $Key => $Value) {
                if (is_array($Value) || \is_object($Value)) {
                    $Value = \serialize($Value);
                }
                try {
                    $KeyExists = \crisp\api\Config::exists("plugin_" . $PluginName . "_$Key");
                    $parsedConfigs[$Key] = $Value;
                    if (!$KeyExists) {
                        $failedConfigs[$Key] = $Value;
                    }
                } catch (\PDOException $ex) {
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

        $PluginPath = \realpath(__DIR__ . "/../../../../$PluginFolder" . DIRECTORY_SEPARATOR . "$PluginName") . DIRECTORY_SEPARATOR;
        if (\file_exists($PluginPath . "sha256sum.txt")) {

            foreach (file($PluginPath . "sha256sum.txt") as $line) {
                if (!ctype_space($line)) {
                    $line = explode("  ", $line);
                    $Hash = $line[0];
                    $File = trim($line[1]);
                    $HashFile = \hash_file("sha256", $PluginPath . $File);
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
     * @see registerInstallHook
     * @param string $PluginName The Folder name of the Plugin
     * @return boolean TRUE if install was successful, otherwise FALSE
     */
    public static function install($PluginName) {

        $DB = new \crisp\core\MySQL();
        $DBConn = $DB->getDBConnector();


        $statement2 = $DBConn->prepare("SELECT * FROM loadedPlugins WHERE Name = :Key");
        $statement2->execute(array(":Key" => $PluginName));

        if ($statement2->rowCount() > 0) {
            return false;
        }

        $PluginFolder = \crisp\api\Config::get("plugin_dir");

        if (!\file_exists(__DIR__ . "/../../../../$PluginFolder/$PluginName/plugin.json")) {
            return false;
        }

        $PluginMetadata = json_decode(\file_get_contents(__DIR__ . "/../../../../$PluginFolder/$PluginName/plugin.json"));


        if (!\is_object($PluginMetadata) && !isset($PluginMetadata->hookFile)) {
            return false;
        }
        self::performOnInstall($PluginName, $PluginMetadata);

        self::broadcastHook("pluginInstall_$PluginName", null);
        self::broadcastHook("pluginInstall", $PluginName);

        $statement = $DBConn->prepare("INSERT INTO loadedPlugins (Name) VALUES (:Key)");
        return $statement->execute(array(":Key" => $PluginName));
    }

    /**
     * Registers an uninstall hook for your plugin.
     * @param string $PluginName
     * @param string|function $Function Callback function, either anonymous or a string to a function
     * @returns boolean TRUE if hook could be registered, otherwise false
     */
    public static function registerUninstallHook($PluginName, $Function) {
        if (\is_callable($Function) || \function_exists($PluginName)($Function)) {
            self::on("pluginUninstall_$PluginName", $Function);
            return true;
        }
        return false;
    }

    public static function registerAfterRenderHook($PluginName, $Function) {
        if (\is_callable($Function) || \function_exists($PluginName)($Function)) {
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
    public static function registerInstallHook($PluginName, $Function) {
        if (\is_callable($Function) || \function_exists($PluginName)($Function)) {
            self::on("pluginInstall_$PluginName", $Function);
            return true;
        }
        return false;
    }

}
