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

    private $PluginFolder;
    private $PluginName;
    private $PluginMetadata;
    private $PluginPath;
    private $TwigTheme;
    public $CurrentFile;
    public $CurrentPage;

    public function __construct($PluginFolder, $PluginName, $PluginMetadata, $TwigTheme, $CurrentFile, $CurrentPage) {
        $this->PluginFolder = $PluginFolder;
        $this->PluginName = $PluginName;
        $this->PluginMetadata = $PluginMetadata;
        $this->PluginPath = realpath(__DIR__ . "/../../../../$PluginFolder/$PluginName/") . "/";
        $this->TwigTheme = $TwigTheme;
        $this->CurrentFile = $CurrentFile;
        $this->CurrentPage = $CurrentPage;
        if (file_exists($this->PluginPath . $PluginMetadata->hookFile)) {
            require $this->PluginPath . $PluginMetadata->hookFile;



            if (file_exists($this->PluginPath . "/templates/views/$CurrentPage.twig") && file_exists($this->PluginPath . "/includes/views/$CurrentPage.php")) {
                require $this->PluginPath . "/includes/views/$CurrentPage.php";

                echo $TwigTheme->render($this->PluginName . "/templates/views/$CurrentPage.twig", TEMPLATE_VARIABLES);

                $this->broadcastHook("pluginAfterRender_".$this->PluginName);

                exit;
            }
        } else {
            throw new Exception("Failed to load plugin " . $this->PluginName . ": Missing hook file");
        }
    }

    public function getConfig($Key) {
        return \crisp\api\Config::get("plugin_" . $this->PluginName . "_$Key");
    }

    public function deleteConfig($Key) {
        return \crisp\api\Config::delete("plugin_" . $this->PluginName . "_$Key");
    }

    public function setConfig($Key, $Value) {
        return \crisp\api\Config::set("plugin_" . $this->PluginName . "_$Key", $Value);
    }

    public function createConfig($Key, $Value) {
        return \crisp\api\Config::create("plugin_" . $this->PluginName . "_$Key", $Value);
    }

    public function uninstall() {
        return \crisp\core\Plugins::uninstall($this->PluginName);
    }

    public function integrity() {
        return \crisp\core\Plugins::integrityCheck($this->PluginName, $this->PluginMetadata, $this->PluginFolder);
    }

    /**
     * Registers an uninstall hook for your plugin.
     * @param string|function $Function Callback function, either anonymous or a string to a function
     * @returns boolean TRUE if hook could be registered, otherwise false
     */
    public function registerUninstallHook($Function) {
        return \crisp\core\Plugins::registerInstallHook($this->PluginName, $Function);
    }

    /**
     * Registers an install hook for your plugin.
     * @param string|function $Function Callback function, either anonymous or a string to a function
     * @returns boolean TRUE if hook could be registered, otherwise false
     */
    public function registerInstallHook($Function) {
        return \crisp\core\Plugins::registerUninstallHook($this->PluginName, $Function);
    }

    public function registerAfterRenderHook($Function) {
        return \crisp\core\Plugins::registerAfterRenderHook($this->PluginName, $Function);
    }

}
