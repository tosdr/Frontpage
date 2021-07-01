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
use crisp\Experiments;
use crisp\Universe;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 * Used internally, plugin loader
 *
 */
class Theme
{

    use Hook;

    private Environment $TwigTheme;
    public string $CurrentFile;
    public string $CurrentPage;

    /**
     * Add an item to the theme's navigation bar
     * @param string $ID Unique string to identify the item
     * @param string $Text The HTML of the navbar item
     * @param string $Link The Link of the navbar item
     * @param string $Target HTML a=target
     * @param int $Order The order to appear on the navbar
     * @param string $Placement Placed left or right of the navbar if supported by theme
     * @return boolean
     * @see https://developer.mozilla.org/en-US/docs/Web/HTML/Element/a#target Link Target
     */
    public static function addToNavbar(string $ID, string $Text, string $Link, string $Target = '_self', int $Order = 0, string $Placement = 'left'): bool
    {
        if ($Placement == 'right') {

            $GLOBALS['navbar_right'][$ID] = array('ID' => $ID, 'html' => $Text, 'href' => $Link, 'target' => $Target, 'order' => $Order);

            usort($GLOBALS['navbar_right'], function ($a, $b) {
                return $a['order'] <=> $b['order'];
            });
            return true;
        }
        $GLOBALS['navbar'][$ID] = array('ID' => $ID, 'html' => $Text, 'href' => $Link, 'target' => $Target, 'order' => $Order);

        usort($GLOBALS['navbar'], function ($a, $b) {
            return $a['order'] <=> $b['order'];
        });
        return true;
    }

    /**
     * Load a theme page
     * @param Environment $TwigTheme The twig theme component
     * @param string $CurrentFile The current file, __FILE__
     * @param string $CurrentPage The current page template to render
     * @throws BitmaskException
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function __construct(Environment $TwigTheme, string $CurrentFile, string $CurrentPage)
    {
        $this->TwigTheme = $TwigTheme;
        $this->CurrentFile = $CurrentFile;
        $this->CurrentPage = $CurrentPage;


        if (CURRENT_UNIVERSE === Universe::UNIVERSE_BETA && Helper::templateExists(\crisp\api\Config::get('theme'), "/_beta/views/$CurrentPage.twig") && file_exists(__DIR__ . '/../../../../' . \crisp\api\Config::get('theme_dir') . '/' . \crisp\api\Config::get('theme') . "/includes/_beta/$CurrentPage.php")) {

            require __DIR__ . '/../../../../' . \crisp\api\Config::get('theme_dir') . '/' . \crisp\api\Config::get('theme') . "/includes/_beta/$CurrentPage.php";

            $_vars = ($_vars ?? []);
            $_vars['template'] = $this;


            $GLOBALS['microtime']['logic']['end'] = microtime(true);
            $GLOBALS['microtime']['template']['start'] = microtime(true);
            $TwigTheme->addGlobal('LogicMicroTime', ($GLOBALS['microtime']['logic']['end'] - $GLOBALS['microtime']['logic']['start']));
            header('X-CMS-LogicTime: ' . ($GLOBALS['microtime']['logic']['end'] - $GLOBALS['microtime']['logic']['start']));
            echo $TwigTheme->render("/_beta/views/$CurrentPage.twig", $_vars);
        } else if (CURRENT_UNIVERSE === Universe::UNIVERSE_DEV && Helper::templateExists(\crisp\api\Config::get('theme'), "/_dev/views/$CurrentPage.twig") && file_exists(__DIR__ . '/../../../../' . \crisp\api\Config::get('theme_dir') . '/' . \crisp\api\Config::get('theme') . "/includes/_dev/$CurrentPage.php")) {

            require __DIR__ . '/../../../../' . \crisp\api\Config::get('theme_dir') . '/' . \crisp\api\Config::get('theme') . "/includes/_dev/$CurrentPage.php";

            $_vars = ($_vars ?? []);
            $_vars['template'] = $this;


            $GLOBALS['microtime']['logic']['end'] = microtime(true);
            $GLOBALS['microtime']['template']['start'] = microtime(true);
            $TwigTheme->addGlobal('LogicMicroTime', ($GLOBALS['microtime']['logic']['end'] - $GLOBALS['microtime']['logic']['start']));
            header('X-CMS-LogicTime: ' . ($GLOBALS['microtime']['logic']['end'] - $GLOBALS['microtime']['logic']['start']));
            echo $TwigTheme->render("/_dev/views/$CurrentPage.twig", $_vars);
        } else if (Helper::templateExists(\crisp\api\Config::get('theme'), "_prod/views/$CurrentPage.twig") && file_exists(__DIR__ . '/../../../../' . \crisp\api\Config::get('theme_dir') . '/' . \crisp\api\Config::get('theme') . "/includes/_prod/$CurrentPage.php")) {
            if (Experiments::hasAnyExperiment()) {

                $Experiments = Experiments::getConstants();
                $Loaded = false;
                foreach (Experiments::getExperiments() as $Experiment) {

                    if ($Experiment === Experiments::NONE) {
                        continue;
                    }

                    $ExperimentName = array_search($Experiment, $Experiments, true);
                    if ($ExperimentName !== null && Helper::templateExists(\crisp\api\Config::get('theme'), "_experiments/$ExperimentName/views/$CurrentPage.twig") && file_exists(__DIR__ . '/../../../../' . \crisp\api\Config::get('theme_dir') . '/' . \crisp\api\Config::get('theme') . "/includes/_experiments/$ExperimentName/$CurrentPage.php")) {
                        require __DIR__ . '/../../../../' . \crisp\api\Config::get('theme_dir') . '/' . \crisp\api\Config::get('theme') . "/includes/_experiments/$ExperimentName/$CurrentPage.php";

                        $_vars = ($_vars ?? []);
                        $_vars['template'] = $this;


                        $GLOBALS['microtime']['logic']['end'] = microtime(true);
                        $GLOBALS['microtime']['template']['start'] = microtime(true);
                        $TwigTheme->addGlobal('LogicMicroTime', ($GLOBALS['microtime']['logic']['end'] - $GLOBALS['microtime']['logic']['start']));
                        header('X-CMS-LogicTime: ' . ($GLOBALS['microtime']['logic']['end'] - $GLOBALS['microtime']['logic']['start']));
                        echo $TwigTheme->render("_experiments/$ExperimentName/views/$CurrentPage.twig", $_vars);
                        $Loaded = true;
                        break;
                    }
                }


                if (!$Loaded) {
                    require __DIR__ . '/../../../../' . \crisp\api\Config::get('theme_dir') . '/' . \crisp\api\Config::get('theme') . "/includes/_prod/$CurrentPage.php";

                    $_vars = ($_vars ?? []);
                    $_vars['template'] = $this;


                    $GLOBALS['microtime']['logic']['end'] = microtime(true);
                    $GLOBALS['microtime']['template']['start'] = microtime(true);
                    $TwigTheme->addGlobal('LogicMicroTime', ($GLOBALS['microtime']['logic']['end'] - $GLOBALS['microtime']['logic']['start']));
                    header('X-CMS-LogicTime: ' . ($GLOBALS['microtime']['logic']['end'] - $GLOBALS['microtime']['logic']['start']));
                    echo $TwigTheme->render("_prod/views/$CurrentPage.twig", $_vars);
                }
            } else {


                require __DIR__ . '/../../../../' . \crisp\api\Config::get('theme_dir') . '/' . \crisp\api\Config::get('theme') . "/includes/_prod/$CurrentPage.php";

                $_vars = ($_vars ?? []);
                $_vars['template'] = $this;


                $GLOBALS['microtime']['logic']['end'] = microtime(true);
                $GLOBALS['microtime']['template']['start'] = microtime(true);
                $TwigTheme->addGlobal('LogicMicroTime', ($GLOBALS['microtime']['logic']['end'] - $GLOBALS['microtime']['logic']['start']));
                header('X-CMS-LogicTime: ' . ($GLOBALS['microtime']['logic']['end'] - $GLOBALS['microtime']['logic']['start']));
                echo $TwigTheme->render("_prod/views/$CurrentPage.twig", $_vars);
            }
        } else {
            throw new BitmaskException('Failed to load template ' . $this->CurrentPage . ': Missing includes file', Bitmask::THEME_MISSING_INCLUDES);
        }
    }

}
