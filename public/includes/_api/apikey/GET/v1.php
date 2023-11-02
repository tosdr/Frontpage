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

use crisp\core\Bitmask;
use crisp\core\PluginAPI;

if(!defined('CRISP_COMPONENT')){
    echo 'Cannot access this component directly!';
    exit;
}

$apikey = ($_GET['apikey'] ?? $this->Query);


if (empty($apikey)) {
    PluginAPI::response(Bitmask::MISSING_PARAMETER, 'apikey parameter missing', []);
    return;
}


$details = crisp\api\Helper::getAPIKeyDetails($apikey);

if (!$details) {
    PluginAPI::response(Bitmask::QUERY_FAILED, 'Invalid apikey', $details);
    return;
}

echo PluginAPI::response(Bitmask::REQUEST_SUCCESS, 'OK', $details);
