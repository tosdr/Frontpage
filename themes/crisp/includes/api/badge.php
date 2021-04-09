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

use PUGX\Poser\Render\SvgFlatRender;
use PUGX\Poser\Poser;

$render = new SvgFlatRender();
$poser = new Poser($render);
$Prefix = \crisp\api\Config::get("badge_prefix");
$Language = $GLOBALS["route"]->Language;
$ServiceName = $this->Query;
$Color;
$Type = pathinfo($this->Query, PATHINFO_EXTENSION);
$RedisData;

if (strpos($this->Query, "_")) {
    $Language = explode("_", $this->Query)[0];
    $ServiceName = explode("_", $this->Query)[1];
}
if ($Type != "") {
    $ServiceName = substr($ServiceName, 0, (strlen($Type) + 1) * -1);
}

$Translations = new \crisp\api\Translation($Language);

if (!is_numeric($ServiceName)) {
    if (!\crisp\api\Phoenix::serviceExistsBySlugPG(urldecode($ServiceName))) {
        header("Content-Type: image/svg+xml");
        $Color = "999999";
        $Rating = $Translations->fetch("service_not_found");

        echo $poser->generate($Prefix, $Rating, $Color, 'flat');
        return;
    }
    $RedisData["_source"] = \crisp\api\Phoenix::getServiceBySlugPG(urldecode($ServiceName));
} else {
    if (count(crisp\api\Phoenix::serviceExistsPG($ServiceName)) === 0) {
        header("Content-Type: image/svg+xml");
        $Color = "999999";
        $Rating = $Translations->fetch("service_not_found");

        echo $poser->generate($Prefix, $Rating, $Color, 'flat');
        return;
    }
    $RedisData = \crisp\api\Phoenix::getServicePG(urldecode($ServiceName));
}


switch ($RedisData["_source"]["is_comprehensively_reviewed"] ? ($RedisData["_source"]["rating"]) : false) {
    case "A":
        $Color = "46A546";
        $Rating = $Translations->fetch("badges.grade.a");
        break;
    case "B":
        $Color = "79B752";
        $Rating = $Translations->fetch("badges.grade.b");
        break;
    case "C":
        $Color = "F89406";
        $Rating = $Translations->fetch("badges.grade.c");
        break;
    case "D":
        $Color = "D66F2C";
        $Rating = $Translations->fetch("badges.grade.d");
        break;
    case "E":
        $Color = "C43C35";
        $Rating = $Translations->fetch("badges.grade.e");
        break;
    default:
        $Color = "999999";
        $Rating = $Translations->fetch("badges.grade.none");
}

$Prefix = \crisp\api\Config::get("badge_prefix") . "/#" . htmlentities($RedisData["_source"]["slug"]);

$SVG = $poser->generate($Prefix, $Rating, $Color, 'flat');

if (!file_exists(__DIR__ . "/../../../../pixelcatproductions/cache/badges/")) {
    mkdir(__DIR__ . "/../../../../pixelcatproductions/cache/badges/");
}

if (time() - filemtime(__DIR__ . "/../../../../pixelcatproductions/cache/badges/" . sha1($Prefix . $RedisData["_source"]["id"] . $Language) . ".svg") > 900) {
    file_put_contents(__DIR__ . "/../../../../pixelcatproductions/cache/badges/" . sha1($Prefix . $RedisData["_source"]["id"] . $Language) . ".svg", $SVG);
}

if ($GLOBALS["route"]->Page === "badgepng" || $Type == "png") {
    header("Content-Type: image/png");

    if (!file_exists(__DIR__ . "/../../../../pixelcatproductions/cache/badges/" . sha1($Prefix . $RedisData["_source"]["id"] . $Language) . ".svg")) {
        echo \crisp\core\PluginAPI::response(\crisp\core\Bitmask::GENERATE_FAILED, "FS Source SVG not found", [], null, 500);
        exit;
    }

    if (time() - filemtime(__DIR__ . "/../../../../pixelcatproductions/cache/badges/" . sha1($Prefix . $RedisData["_source"]["id"] . $Language) . ".png") > 900) {

        exec("/usr/bin/inkscape -e \"" . __DIR__ . "/../../../../pixelcatproductions/cache/badges/" . sha1($Prefix . $RedisData["_source"]["id"] . $Language) . ".png\" \"" . __DIR__ . "/../../../../pixelcatproductions/cache/badges/" . sha1($Prefix . $RedisData["_source"]["id"] . $Language) . ".svg\"");

        if (!file_exists(__DIR__ . "/../../../../pixelcatproductions/cache/badges/" . sha1($Prefix . $RedisData["_source"]["id"] . $Language) . ".png")) {
            echo \crisp\core\PluginAPI::response(\crisp\core\Bitmask::GENERATE_FAILED, "FS PNG not found", [], null, 500);
            exit;
        }
    }
    echo file_get_contents(__DIR__ . "/../../../../pixelcatproductions/cache/badges/" . sha1($Prefix . $RedisData["_source"]["id"] . $Language) . ".png");
    exit;
}

header("Content-Type: image/svg+xml");


echo $SVG;
