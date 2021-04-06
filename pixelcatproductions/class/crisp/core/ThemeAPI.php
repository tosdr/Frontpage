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
class ThemeAPI {

    use \crisp\core\Hook;

    public $ThemeFolder;
    public $ThemeName;
    public $Interface;
    public $Query;
    public $TwigTheme;
    public $ThemePath;

    /**
     * 
     * @param string $ThemeFolder The path to your theme
     * @param string $ThemeName The name of your theme
     * @param object $ThemeMetadata Theme.json file contents
     * @param \Twig\Environment $TwigTheme The current twig theme
     * @param string $CurrentFile The current file
     * @param string $CurrentPage The current $_GET["page"] parameter
     * @throws Exception
     */
    public function __construct($ThemeLoader, $Interface, $_QUERY) {
        $this->Interface = \crisp\api\Helper::filterAlphaNum($Interface);
        $this->Query = $_QUERY;
        $this->TwigTheme = $ThemeLoader;
        $this->ThemePath = realpath(__DIR__ . "/../../../../" . \crisp\api\Config::get("theme_dir") . "/" . \crisp\api\Config::get("theme") . "/");

        if (file_exists($this->ThemePath . "/includes/api/" . $this->Interface . ".php")) {
            require $this->ThemePath . "/includes/api/" . $this->Interface . ".php";
            exit;
        }
    }

}
