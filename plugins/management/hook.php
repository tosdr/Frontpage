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

include __DIR__ . '/includes/Users.php';
include __DIR__ . '/includes/PhoenixUser.php';

if (isset($_SESSION[\crisp\core\Config::$Cookie_Prefix . "session_login"])) {

    $User = new \crisp\plugin\curator\PhoenixUser($_SESSION[\crisp\core\Config::$Cookie_Prefix . "session_login"]["user"]);

    if (!$User->isSessionValid()) {
        unset($_SESSION[\crisp\core\Config::$Cookie_Prefix . "session_login"]);
    } else {
        \crisp\core\Theme::addToNavbar("curator", '<span class="badge badge-info"><i class="fas fa-hands-helping"></i> MANAGE</span>', "/dashboard", "_self", -1, "right");
    }

    $userDetails = $User->fetch();

    $_locale = \crisp\api\Helper::getLocale();

    /* Navbar */

        $navbar[] = array("ID" => "dashboard", "html" => $this->getTranslation('views.curator_dashboard.header'), "href" => "/$_locale/dashboard", "target" => "_self");


    if ($userDetails["curator"]) {
        $navbar[] = array("ID" => "service_requests", "html" => $this->getTranslation('views.service_requests.header'), "href" => "/$_locale/service_requests", "target" => "_self");
    }


        $navbar[] = array("ID" => "apikeys", "html" => $this->getTranslation('views.apikeys.header'), "href" => "/$_locale/apikeys", "target" => "_self");

    $this->TwigTheme->addGlobal("route", $GLOBALS["route"]->GET);
    $this->TwigTheme->addGlobal("management_navbar", $navbar);
    $this->TwigTheme->addGlobal("api_permissions", \crisp\core\APIPermissions::getConstants());
    $this->TwigTheme->addFunction(new \Twig\TwigFunction('fetch_phoenix_user', [new \crisp\plugin\curator\PhoenixUser(), 'fetchStatic']));
    $this->TwigTheme->addFilter(new \Twig\TwigFilter('strtotime', 'strtotime'));
    $this->TwigTheme->addFunction(new \Twig\TwigFunction('time', 'time'));
} else {
    \crisp\core\Theme::addToNavbar("login", $this->getTranslation("views.login.header"), "/login", "_self", 99);
}
