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


if (!is_numeric($_GET["service"] ?? $this->Query)) {
    if (!crisp\api\Phoenix::serviceExistsBySlugPG($_GET["service"] ?? $this->Query)) {
        echo \crisp\core\PluginAPI::response(\crisp\core\Bitmask::INVALID_SERVICE + \crisp\core\Bitmask::VERSION_DEPRECATED, $_GET["service"] ?? $this->Query, []);
        return;
    }
    $_GET["service"] ?? $this->Query = crisp\api\Phoenix::getServiceBySlugPG($_GET["service"] ?? $this->Query)["id"];
    echo \crisp\core\PluginAPI::response(\crisp\core\Bitmask::REQUEST_SUCCESS + \crisp\core\Bitmask::VERSION_DEPRECATED, $_GET["service"] ?? $this->Query, \crisp\api\Phoenix::generateApiFiles($_GET["service"] ?? $this->Query, "3"));
    exit;
}

if (!crisp\api\Phoenix::serviceExistsPG($_GET["service"] ?? $this->Query)) {
    echo \crisp\core\PluginAPI::response(\crisp\core\Bitmask::INVALID_SERVICE + \crisp\core\Bitmask::VERSION_DEPRECATED, $_GET["service"] ?? $this->Query, []);
    return;
}



echo \crisp\core\PluginAPI::response(\crisp\core\Bitmask::REQUEST_SUCCESS + \crisp\core\Bitmask::VERSION_DEPRECATED, $_GET["service"] ?? $this->Query, \crisp\api\Phoenix::generateApiFiles($_GET["service"] ?? $this->Query, "3"));
