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