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

\crisp\core\Theme::addtoNavbar("frontpage", crisp\api\Translation::fetch("components.navbar.home"), \crisp\api\Helper::generateLink("frontpage"), "_self", -99);
\crisp\core\Theme::addtoNavbar("ratings", crisp\api\Translation::fetch("components.navbar.ratings"), \crisp\api\Helper::generateLink("frontpage#ratings"), "_self", -98);
\crisp\core\Theme::addtoNavbar("api", crisp\api\Translation::fetch("components.navbar.api"), \crisp\api\Helper::generateLink("api"), "_self", -95);
\crisp\core\Theme::addtoNavbar("forum", crisp\api\Translation::fetch("components.navbar.forum"), \crisp\api\Helper::generateLink(\crisp\api\Config::get("forum_url"), true), "_self", -94);
\crisp\core\Theme::addtoNavbar("presskit", crisp\api\Translation::fetch("components.navbar.presskit"), \crisp\api\Helper::generateLink("presskit"), "_self", -93);
\crisp\core\Theme::addtoNavbar("status", crisp\api\Translation::fetch("components.navbar.status"), \crisp\api\Helper::generateLink(\crisp\api\Config::get("status_url"), true), "_self", -92);
\crisp\core\Theme::addtoNavbar("donate", crisp\api\Translation::fetch("components.navbar.donate"), \crisp\api\Helper::generateLink(\crisp\api\Config::get("opencollective_url"), true), "_self", 0, "right");
\crisp\core\Theme::addtoNavbar("about", crisp\api\Translation::fetch("components.navbar.about"), \crisp\api\Helper::generateLink("about"), "_self", -97);
\crisp\core\Theme::addtoNavbar("downloads", crisp\api\Translation::fetch("components.navbar.download"), \crisp\api\Helper::generateLink("downloads"), "_self", -96);
\crisp\core\Theme::addtoNavbar("new_service", crisp\api\Translation::fetch("components.navbar.request_service"), \crisp\api\Helper::generateLink("new_service"), "_self", 100);
if (explode("/", $_GET["route"])[1] !== "api") {
    if (\crisp\api\Config::get("maintenance_enabled") || isset($_GET["simulate_maintenance"])) {
        http_response_code(503);
        echo $TwigTheme->render("errors/maintenance.twig");
        exit;
    }
    if (\crisp\api\Config::get("highload_enabled") || isset($_GET["simulate_highload"])) {
        http_response_code(503);
        echo $TwigTheme->render("errors/highload.twig");
        exit;
    }
}