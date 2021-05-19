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
class ThemeAPI
{

    use Hook;

    public string $Interface;
    public string $Query;
    public string $ThemePath;
    public Environment $TwigTheme;

    /**
     *
     * @param Environment $ThemeLoader
     * @param string $Interface
     * @param string $_QUERY
     */
    public function __construct(Environment $ThemeLoader, string $Interface, string $_QUERY)
    {
        $this->Interface = Helper::filterAlphaNum($Interface);
        $this->Query = $_QUERY;
        $this->TwigTheme = $ThemeLoader;
        $this->ThemePath = realpath(__DIR__ . "/../../../../" . \crisp\api\Config::get("theme_dir") . "/" . \crisp\api\Config::get("theme") . "/");

        if (file_exists($this->ThemePath . "/includes/api/" . $this->Interface . ".php")) {
            require $this->ThemePath . "/includes/api/" . $this->Interface . ".php";
            exit;
        }
    }

}
