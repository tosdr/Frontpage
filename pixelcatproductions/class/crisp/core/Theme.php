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
class Theme {

    use \crisp\core\Hook;

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
     * @param \TwigEnvironment $TwigTheme The twig theme component
     * @param string $CurrentFile The current file, __FILE__
     * @param string $CurrentPage The current page template to render
     * @throws Exception
     */
    public function __construct($TwigTheme, $CurrentFile, $CurrentPage) {
        $this->TwigTheme = $TwigTheme;
        $this->CurrentFile = $CurrentFile;
        $this->CurrentPage = $CurrentPage;
        if (\crisp\api\Helper::templateExists(\crisp\api\Config::get("theme"), "/views/$CurrentPage.twig")) {

            if (file_exists(__DIR__ . "/../../../../" . \crisp\api\Config::get("theme_dir") . "/" . \crisp\api\Config::get("theme") . "/includes/$CurrentPage.php") && \crisp\api\Helper::templateExists(\crisp\api\Config::get("theme"), "/views/$CurrentPage.twig")) {

                require __DIR__ . "/../../../../" . \crisp\api\Config::get("theme_dir") . "/" . \crisp\api\Config::get("theme") . "/includes/$CurrentPage.php";

                $_vars = (isset($_vars) ? $_vars : [] );
                $_vars["template"] = $this;


                $GLOBALS["microtime"]["logic"]["end"] = microtime(true);
                $GLOBALS["microtime"]["template"]["start"] = microtime(true);
                $TwigTheme->addGlobal("LogicMicroTime", ($GLOBALS["microtime"]["logic"]["end"] - $GLOBALS["microtime"]["logic"]["start"]));
                header("X-CMS-LogicTime: ". ($GLOBALS["microtime"]["logic"]["end"] - $GLOBALS["microtime"]["logic"]["start"]));
                echo $TwigTheme->render("views/$CurrentPage.twig", $_vars);
            }
        } else {
            throw new Exception("Failed to load template " . $this->CurrentPage . ": Missing includes file");
        }
    }

}
