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
            throw new \Exception("Plugin <b>" . $this->PluginName . "</b> failed to load due to a missing includes file");
        }
    }

    /**
     * @see \crisp\api\Translation::fetch
     */
    public function getTranslation($Key, $Count = 1, $UserOptions = array()) {

        $Locale = \crisp\api\Helper::getLocale();


        $Translation = new \crisp\api\Translation($Locale);


        return $Translation->fetch("plugin_" . $this->PluginName . "_$Key", $Count, $UserOptions);
    }

    /**
     * @see \crisp\api\Config::get
     */
    public function getConfig($Key) {
        return \crisp\api\Config::get("plugin_" . $this->PluginName . "_$Key");
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
        
        if(!file_exists(__DIR__ . "/../../../../" . \crisp\api\Config::get("plugin_dir") . "/" . $this->PluginName . "/$File")){
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
