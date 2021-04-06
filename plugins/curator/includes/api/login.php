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


/** @var \crisp\core\PluginAPI $this */
/** @var \crisp\core\PluginAPI self */
include __DIR__ . '/../Users.php';
include __DIR__ . '/../PhoenixUser.php';




$User = \crisp\plugin\curator\Users::fetchByEmail($_POST["email"]);

if (!$User) {
    $this->response(array("INVALID_EMAIL", "INVALID_PASSWORD"), "Error");
    exit;
}

if ($User->UserID === false) {
    $this->response(array("INVALID_EMAIL"), "E-Mail is invalid");
    exit;
}

if (!$User->verifyPassword($_POST["password"])) {
    $this->response(array("INVALID_PASSWORD"), "Password is invalid");
    exit;
}
if (!$User->fetch()["curator"]) {
    $this->response(array("NOT_CURATOR"), "You are not an curator!");
    exit;
}
if (!$User->createSession()) {
    $this->response(array("SESSION_ERROR"), "Failed to create session");
    exit;
}
$this->response(false, "OK");
