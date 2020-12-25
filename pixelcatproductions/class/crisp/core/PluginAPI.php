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
class PluginAPI {

    use \crisp\core\Hook;

    public $PluginFolder;
    public $PluginName;
    public $Interface;
    public $Query;
    public $PluginPath;

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
    public function __construct($PluginFolder, $PluginName, $Interface, $_QUERY) {
        $this->PluginFolder = \crisp\api\Helper::filterAlphaNum($PluginFolder);
        $this->PluginName = \crisp\api\Helper::filterAlphaNum($PluginName);
        $this->Interface = \crisp\api\Helper::filterAlphaNum($Interface);
        $this->Query = $_QUERY;
        $this->PluginPath = realpath(__DIR__ . "/../../../../" . $this->PluginFolder . "/" . $this->PluginName . "/");

        if (file_exists($this->PluginPath . "/includes/api/" . $this->Interface . ".php")) {
            require $this->PluginPath . "/includes/api/" . $this->Interface . ".php";
            exit;
        }
    }

    /**
     * Send a JSON response
     * @param array|bool $Errors Error array or false
     * @param string $message A message to send
     * @param array $Parameters Some response parameters
     * @param constant $Flags JSON_ENCODE constants
     */
    public static function response($Errors, string $message, array $Parameters = [], $Flags = null, $HTTP = 200) {
        header("Content-Type: application/json");
        http_response_code($HTTP);
        echo json_encode(array("error" => $Errors, "message" => $message, "parameters" => $Parameters), $Flags);
    }

}
