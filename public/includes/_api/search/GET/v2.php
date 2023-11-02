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

$inputQuery = null;

$inputQuery = $_GET['query'] ?? $inputQuery;

if (empty($inputQuery) || !isset($inputQuery)) {
    PluginAPI::response(crisp\core\Bitmask::QUERY_FAILED + Bitmask::VERSION_DEPRECATED, 'Empty request', ([
        'results' => 0,
        'service' => []
    ]));
    exit;
}


foreach (crisp\api\Phoenix::searchServiceByName(strtolower($inputQuery)) as $Service) {
    $Array[] = $Service;
}

if (count($Array) > 0) {
    PluginAPI::response(Bitmask::REQUEST_SUCCESS + Bitmask::VERSION_DEPRECATED, $inputQuery, ([
        'results' => count($Array),
        'service' => $Array
    ]));
    exit;
}
PluginAPI::response(Bitmask::REQUEST_SUCCESS + crisp\core\Bitmask::QUERY_FAILED + Bitmask::VERSION_DEPRECATED, $inputQuery, ([
    'results' => 0,
    'service' => $Array
]));
