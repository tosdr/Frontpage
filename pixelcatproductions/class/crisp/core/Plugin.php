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

/**
 * Used internally, plugin loader
 *
 */
class Plugin {

    use \crisp\core\Hook;

    public $PluginFolder;
    public $PluginName;
    public $PluginMetadata;
    public $PluginPath;
    public $TwigTheme;
    public $CurrentFile;
    public $CurrentPage;

    /**
     * 
     * @param string $PluginFolder The path to your plugin
     * @param string $PluginName The name of your plugin
     * @param object $PluginMetadata Plugin.json file contents
     * @param \Twig\Environment $TwigTheme The current twig theme
     * @param string $CurrentFile The current file
     * @param string $CurrentPage The current $_GET["page"] parameter
     * @throws Exception
     */
    public function __construct($PluginFolder, $PluginName, $PluginMetadata, $TwigTheme, $CurrentFile, $CurrentPage) {
        $this->PluginFolder = \crisp\api\Helper::filterAlphaNum($PluginFolder);
        $this->PluginName = \crisp\api\Helper::filterAlphaNum($PluginName);
        $this->PluginMetadata = $PluginMetadata;
        $this->PluginPath = realpath(__DIR__ . "/../../../../" . $PluginFolder . "/" . $PluginName . "/") . "/";
        $this->TwigTheme = $TwigTheme;
        $this->CurrentFile = \crisp\api\Helper::filterAlphaNum($CurrentFile);
        $this->CurrentPage = \crisp\api\Helper::filterAlphaNum($CurrentPage);
        if (file_exists($this->PluginPath . $PluginMetadata->hookFile)) {
            require $this->PluginPath . $PluginMetadata->hookFile;



            if (file_exists($this->PluginPath . "/templates/views/" . $this->CurrentPage . ".twig") && file_exists($this->PluginPath . "/includes/views/" . $this->CurrentPage . ".php")) {
                $GLOBALS["plugins"][] = $this;
                require $this->PluginPath . "/includes/views/" . $this->CurrentPage . ".php";


                $_vars = (isset($_vars) ? $_vars : [] );
                $_vars["plugin"] = $this;

                $GLOBALS["render"][$this->PluginName . "/templates/views/" . $this->CurrentPage . ".twig"] = $_vars;

                unset($_vars);
            }
        } else {
            throw new \crisp\exceptions\BitmaskException("Plugin <b>" . $this->PluginName . "</b> failed to load due to a missing includes file", Bitmask::PLUGIN_MISSING_INCLUDES);
        }
    }

    /**
     * @see \crisp\api\Translation::fetch
     */
    public function getTranslation($Key, $Count = 1, $UserOptions = array()) {

        $Locale = \crisp\api\Helper::getLocale();


        $Translation = new \crisp\api\Translation($Locale);


        return $Translation->fetch("plugin." . $this->PluginName . ".$Key", $Count, $UserOptions);
    }

    /**
     * @see \crisp\api\Config::get
     */
    public function getConfig($Key) {
        return \crisp\api\Config::get("plugin." . $this->PluginName . ".$Key");
    }

    /**
     * @see \crisp\api\lists\Cron::create
     */
    public function createCron(string $Type, $Data, string $Interval = "2 MINUTE", bool $ExecuteOnce = false) {
        return \crisp\api\lists\Cron::create("execute_plugin_cron", json_encode(array("data" => $Data, "name" => $Type)), $Interval, $this->PluginName, $ExecuteOnce);
    }

    public function includeResource($File) {
        if (strpos($File, "/") === 0) {
            $File = substr($File, 1);
        }

        if (!file_exists(__DIR__ . "/../../../../" . \crisp\api\Config::get("plugin_dir") . "/" . $this->PluginName . "/$File")) {
            return (\crisp\api\Config::exists("cdn") ? \crisp\api\Config::get("cdn") : "") . "/" . \crisp\api\Config::get("plugin_dir") . "/" . $this->PluginName . "/$File";
        }

        return (\crisp\api\Config::exists("cdn") ? \crisp\api\Config::get("cdn") : "") . "/" . \crisp\api\Config::get("plugin_dir") . "/" . $this->PluginName . "/$File?" . hash_file("sha256", __DIR__ . "/../../../../" . \crisp\api\Config::get("plugin_dir") . "/" . $this->PluginName . "/$File");
    }

    /**
     * @see \crisp\api\Config::delete
     */
    public function deleteConfig($Key) {
        return \crisp\api\Config::delete("plugin_" . $this->PluginName . "_$Key");
    }

    /**
     * @see \crisp\api\Config::set
     */
    public function setConfig($Key, $Value) {
        return \crisp\api\Config::set("plugin_" . $this->PluginName . "_$Key", $Value);
    }

    /**
     * @see \crisp\api\Config::create
     */
    public function createConfig($Key, $Value) {
        return \crisp\api\Config::create("plugin_" . $this->PluginName . "_$Key", $Value);
    }

    /**
     * @see \crisp\core\Plugins::listConfig
     */
    public function listConfig() {
        return Plugins::listConfig($this->PluginName);
    }

    /**
     * Uninstall a plugin
     * @return bool
     */
    public function uninstall() {
        return \crisp\core\Plugins::uninstall($this->PluginName, $this->TwigTheme, $this->CurrentFile, $this->CurrentPage);
    }

    /**
     * Check the integrity of a plugin
     * @return array
     */
    public function integrity() {
        return \crisp\core\Plugins::integrityCheck($this->PluginName, $this->PluginMetadata, $this->PluginFolder);
    }

    /**
     * Registers an uninstall hook for your plugin.
     * @param string|function $Function Callback function, either anonymous or a string to a function
     * @returns boolean TRUE if hook could be registered, otherwise false
     */
    public function registerUninstallHook($Function) {
        return \crisp\core\Plugins::registerUninstallHook($this->PluginName, $Function);
    }

    /**
     * Registers an install hook for your plugin.
     * @param string|function $Function Callback function, either anonymous or a string to a function
     * @returns boolean TRUE if hook could be registered, otherwise false
     */
    public function registerInstallHook($Function) {
        return \crisp\core\Plugins::registerInstallHook($this->PluginName, $Function);
    }

    /**
     * Registers a hook thats called after a template has rendered in your plugin.
     * @param string|function $Function Callback function, either anonymous or a string to a function
     * @returns boolean TRUE if hook could be registered, otherwise false
     */
    public function registerAfterRenderHook($Function) {
        return \crisp\core\Plugins::registerAfterRenderHook($this->PluginName, $Function);
    }

}
