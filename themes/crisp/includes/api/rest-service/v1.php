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

use crisp\core\PluginAPI;

if(!defined('CRISP_COMPONENT')){
    echo 'Cannot access this component directly!';
    exit;
}

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        require_once __DIR__ . '/GET/v1.php';
        break;
    case 'POST':

        if (!crisp\api\Helper::hasApiPermissions(crisp\core\APIPermissions::POST_SERVICE_REQUEST)) {
            PluginAPI::response(crisp\core\Bitmask::MISSING_PERMISSIONS, 'Missing Permissions ' . crisp\core\APIPermissions::getBitmask(crisp\core\APIPermissions::POST_SERVICE_REQUEST, true)[0], [], null, 403);
            return;
        }

        PluginAPI::response(crisp\core\Bitmask::NOT_IMPLEMENTED, 'Not yet implemented', [], null, 405);
        break;
    default:
        PluginAPI::response(crisp\core\Bitmask::NOT_IMPLEMENTED, 'Invalid Request Method', [], null, 405);
}