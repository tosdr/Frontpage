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

if ($this->Query == "all") {
    $Services = \crisp\api\Phoenix::getServicesPG();
    $Response = array(
        "tosdr/api/version" => 1,
        "tosdr/data/version" => time(),
    );
    foreach ($Services as $Service) {
        $URLS = explode(",", $Service["url"]);
        foreach ($URLS as $URL) {
            $URL = trim($URL);
            $Response["tosdr/review/$URL"] = array(
                "id" => (int) $Service["id"],
                "documents" => [],
                "logo" => \crisp\api\Config::get("s3_logos") . "/" . $Service["id"] . ".png",
                "name" => $Service["name"],
                "slug" => $Service["slug"],
                "rated" => ($Service["rating"] == "N/A" ? false : ($Service["is_comprehensively_reviewed"] ? $Service["rating"] : false)),
                "points" => []
            );
        }
    }
    echo json_encode($Response);
    return;
}

if (!is_numeric($this->Query)) {
    if (!crisp\api\Phoenix::serviceExistsBySlugPG($this->Query)) {
        echo \crisp\core\PluginAPI::response(\crisp\core\Bitmask::INVALID_SERVICE + crisp\core\Bitmask::VERSION_DEPRECATED + crisp\core\Bitmask::INTERFACE_DEPRECATED, $this->Query, [], null, 404);
        return;
    }
    $this->Query = crisp\api\Phoenix::getServiceBySlugPG($this->Query)["id"];
    $SkeletonData = \crisp\api\Phoenix::generateApiFiles($this->Query);
    echo \crisp\core\PluginAPI::response(\crisp\core\Bitmask::VERSION_DEPRECATED + crisp\core\Bitmask::REQUEST_SUCCESS + crisp\core\Bitmask::INTERFACE_DEPRECATED, $this->Query, $SkeletonData);


    exit;
}

if (!crisp\api\Phoenix::serviceExistsPG($this->Query)) {
    echo \crisp\core\PluginAPI::response(\crisp\core\Bitmask::INVALID_SERVICE + crisp\core\Bitmask::VERSION_DEPRECATED + crisp\core\Bitmask::INTERFACE_DEPRECATED, $this->Query, [], null, 404);
    return;
}

$SkeletonData = \crisp\api\Phoenix::generateApiFiles($this->Query);

echo \crisp\core\PluginAPI::response(crisp\core\Bitmask::VERSION_DEPRECATED + crisp\core\Bitmask::REQUEST_SUCCESS + crisp\core\Bitmask::INTERFACE_DEPRECATED, $this->Query, $SkeletonData);


