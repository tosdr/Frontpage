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
        \crisp\core\Theme::addtoNavbar("curator", '<span class="badge badge-info"><i class="fas fa-hands-helping"></i> CURATOR</span>', "/curator_dashboard", "_self", -1, "right");
    }
} else {
    \crisp\core\Theme::addtoNavbar("login", $this->getTranslation("views.login.header"), "/login", "_self", 99);
}