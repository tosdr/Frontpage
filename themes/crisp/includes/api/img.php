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

header("Access-Control-Allow-Origin: *");


if (!isset($GLOBALS["route"]->GET["theme"])) {
    \crisp\api\Helper::PlaceHolder("Invalid Theme");
}
if (!isset($GLOBALS["route"]->GET["logo"])) {
    \crisp\api\Helper::PlaceHolder("Invalid Service");
}

if (file_exists(__DIR__ . "/../themes/" . $GLOBALS["route"]->GET["theme"] . "/img/logo/" . explode("?", $GLOBALS["route"]->GET["logo"])[0])) {
    $ext = pathinfo(__DIR__ . "/../themes/" . $GLOBALS["route"]->GET["theme"] . "/img/logo/" . explode("?", $GLOBALS["route"]->GET["logo"])[0], PATHINFO_EXTENSION);
    if ($ext == "png") {
        header("Content-Type: image/png");
    }
    if ($ext == "svg") {
        header("Content-Type: image/svg+xml");
    }
    if ($ext == "jpg") {
        header("Content-Type: image/jpg");
    }
    echo file_get_contents(__DIR__ . "/../themes/" . $GLOBALS["route"]->GET["theme"] . "/img/logo/" . explode("?", $GLOBALS["route"]->GET["logo"])[0]);
} else {
    \crisp\api\Helper::PlaceHolder("Missing Logo");
}