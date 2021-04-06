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

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    echo \crisp\core\PluginAPI::response(crisp\core\Bitmask::NOT_IMPLEMENTED, "Invalid Request Method", [], null, 405);
    exit;
}

if ($this->Query == "all") {
    $Services = \crisp\api\Phoenix::getServicesPG();
    $Response = array(
        "version" => time(),
    );
    foreach ($Services as $Index => $Service) {

        $Service["urls"] = explode(",", $Service["url"]);
        $Service["nice_service"] = \crisp\api\Helper::filterAlphaNum($Service["name"]);
        $Service["has_image"] = (file_exists(__DIR__ . "/../" . \crisp\api\Config::get("theme_dir") . "/" . \crisp\api\Config::get("theme") . "/img/logo/" . $Service["nice_service"] . ".svg") ? true : file_exists(__DIR__ . "/../" . \crisp\api\Config::get("theme_dir") . "/" . \crisp\api\Config::get("theme") . "/img/logo/" . $Service["nice_service"] . ".png") );
        $Service["logo"] = \crisp\api\Config::get("s3_logos") . "/" . $Service["id"] . ".png";

        $Services[$Index] = $Service;
    }

    $Response["services"] = $Services;

    echo \crisp\core\PluginAPI::response(false, "All services below", $Response);

    return;
}

if (!is_numeric($this->Query)) {
    if (!crisp\api\Phoenix::serviceExistsBySlugPG($this->Query)) {
        echo \crisp\core\PluginAPI::response(\crisp\core\Bitmask::INVALID_SERVICE + crisp\core\Bitmask::VERSION_DEPRECATED + crisp\core\Bitmask::INTERFACE_DEPRECATED, $this->Query, []);
        return;
    }
    $this->Query = crisp\api\Phoenix::getServiceBySlugPG($this->Query)["id"];
    $SkeletonData = \crisp\api\Phoenix::generateApiFiles($this->Query);
    echo \crisp\core\PluginAPI::response(\crisp\core\Bitmask::REQUEST_SUCCESS + crisp\core\Bitmask::VERSION_DEPRECATED + crisp\core\Bitmask::INTERFACE_DEPRECATED, $this->Query, \crisp\api\Phoenix::generateApiFiles($this->Query, "3"));
    exit;
}

if (!crisp\api\Phoenix::serviceExistsPG($this->Query)) {
    echo \crisp\core\PluginAPI::response(\crisp\core\Bitmask::INVALID_SERVICE + crisp\core\Bitmask::VERSION_DEPRECATED + crisp\core\Bitmask::INTERFACE_DEPRECATED, $this->Query, []);
    return;
}


echo \crisp\core\PluginAPI::response(\crisp\core\Bitmask::REQUEST_SUCCESS + crisp\core\Bitmask::VERSION_DEPRECATED + crisp\core\Bitmask::INTERFACE_DEPRECATED, $this->Query, \crisp\api\Phoenix::generateApiFiles($this->Query, "3"));
