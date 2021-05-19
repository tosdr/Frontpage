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
use Twig\Environment;

/**
 * Used internally, plugin loader
 *
 */
class PluginAPI {

    use Hook;

    public string $PluginFolder;
    public string $PluginName;
    public string $Interface;
    public string $Query;
    public string $PluginPath;

    /**
     *
     * @param string $PluginFolder The path to your plugin
     * @param string $PluginName The name of your plugin
     * @param string $Interface
     * @param string $_QUERY
     */
    public function __construct(string $PluginFolder, string $PluginName, string $Interface, string $_QUERY) {
        $this->PluginFolder = Helper::filterAlphaNum($PluginFolder);
        $this->PluginName = Helper::filterAlphaNum($PluginName);
        $this->Interface = Helper::filterAlphaNum($Interface);
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
    public static function response($Errors = Bitmask::NONE, string $message, $Parameters = [], $Flags = null, $HTTP = 200) {
        header("Content-Type: application/json");
        http_response_code($HTTP);
        echo json_encode(array("error" => $Errors, "message" => $message, "parameters" => $Parameters), $Flags);
    }

}
