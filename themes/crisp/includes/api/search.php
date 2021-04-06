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

$Interface = "default";

if (is_array($GLOBALS["route"]->GET)) {
    $Interface = array_key_first($GLOBALS["route"]->GET);

    $this->Query = $GLOBALS["route"]->GET[$Interface];
    if (strpos($GLOBALS["route"]->GET[$Interface], ".json")) {
        $this->Query = substr($this->Query, 0, -5);
    }
}

switch ($Interface) {
    case "v2":
        require_once __DIR__ . '/search/v2.php';
        break;
    case "v3":
        require_once __DIR__ . '/search/v3.php';
        break;
    case "v1":
    default:
        require_once __DIR__ . '/search/v1.php';
}