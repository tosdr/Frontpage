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
use crisp\exceptions\BitmaskException;
use TwigEnvironment;

/**
 * Used internally, plugin loader
 *
 */
class Theme {

    use Hook;

    private $TwigTheme;
    public $CurrentFile;
    public $CurrentPage;

    /**
     * Add an item to the theme's navigation bar
     * @param string $ID Unique string to identify the item
     * @param string $Text The HTML of the navbar item
     * @param string $Link The Link of the navbar item
     * @param string $Target HTML a=target
     * @param int $Order The order to appear on the navbar
     * @param string $Placement Placed left or right of the navbar if supported by theme
     * @see https://developer.mozilla.org/en-US/docs/Web/HTML/Element/a#target Link Target
     * @return boolean
     */
    public static function addtoNavbar($ID, $Text, $Link, $Target = "_self", $Order = 0, $Placement = "left") {
        if ($Placement == "right") {

            $GLOBALS["navbar_right"][$ID] = array("ID" => $ID, "html" => $Text, "href" => $Link, "target" => $Target, "order" => $Order);

            usort($GLOBALS["navbar_right"], function($a, $b) {
                return $a['order'] <=> $b['order'];
            });
            return true;
        }
        $GLOBALS["navbar"][$ID] = array("ID" => $ID, "html" => $Text, "href" => $Link, "target" => $Target, "order" => $Order);

        usort($GLOBALS["navbar"], function($a, $b) {
            return $a['order'] <=> $b['order'];
        });
        return true;
    }

    /**
     * Load a theme page
     * @param TwigEnvironment $TwigTheme The twig theme component
     * @param string $CurrentFile The current file, __FILE__
     * @param string $CurrentPage The current page template to render
     * @throws Exception
     * @throws BitmaskException
     */
    public function __construct($TwigTheme, $CurrentFile, $CurrentPage) {
        $this->TwigTheme = $TwigTheme;
        $this->CurrentFile = $CurrentFile;
        $this->CurrentPage = $CurrentPage;
        if (Helper::templateExists(\crisp\api\Config::get("theme"), "/views/$CurrentPage.twig")) {

            if (file_exists(__DIR__ . "/../../../../" . \crisp\api\Config::get("theme_dir") . "/" . \crisp\api\Config::get("theme") . "/includes/$CurrentPage.php") && Helper::templateExists(\crisp\api\Config::get("theme"), "/views/$CurrentPage.twig")) {

                require __DIR__ . "/../../../../" . \crisp\api\Config::get("theme_dir") . "/" . \crisp\api\Config::get("theme") . "/includes/$CurrentPage.php";

                $_vars = (isset($_vars) ? $_vars : [] );
                $_vars["template"] = $this;


                $GLOBALS["microtime"]["logic"]["end"] = microtime(true);
                $GLOBALS["microtime"]["template"]["start"] = microtime(true);
                $TwigTheme->addGlobal("LogicMicroTime", ($GLOBALS["microtime"]["logic"]["end"] - $GLOBALS["microtime"]["logic"]["start"]));
                header("X-CMS-LogicTime: " . ($GLOBALS["microtime"]["logic"]["end"] - $GLOBALS["microtime"]["logic"]["start"]));
                echo $TwigTheme->render("views/$CurrentPage.twig", $_vars);
            }
        } else {
            throw new BitmaskException("Failed to load template " . $this->CurrentPage . ": Missing includes file", Bitmask::THEME_MISSING_INCLUDES);
        }
    }

}
