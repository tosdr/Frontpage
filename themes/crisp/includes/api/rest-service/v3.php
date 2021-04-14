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


switch ($_SERVER["REQUEST_METHOD"]) {
    case "GET":
        require_once __DIR__ . '/GET/v3.php';
        break;
    case "POST":

        if (!crisp\api\Helper::hasApiPermissions(crisp\core\APIPermissions::POST_SERVICE_REQUEST)) {
            echo \crisp\core\PluginAPI::response(crisp\core\Bitmask::MISSING_PERMISSIONS, "Missing Permissions " . crisp\core\APIPermissions::getBitmask(crisp\core\APIPermissions::POST_SERVICE_REQUEST, true)[0], [], null, 403);
            return;
        }

        echo \crisp\core\PluginAPI::response(crisp\core\Bitmask::NOT_IMPLEMENTED, "Not yet implemented", [], null, 405);
        break;
    default:
        echo \crisp\core\PluginAPI::response(crisp\core\Bitmask::NOT_IMPLEMENTED, "Invalid Request Method", [], null, 405);
}