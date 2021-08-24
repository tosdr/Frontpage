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

use crisp\api\Config;
use crisp\core;
use crisp\core\Bitmask;
use crisp\core\PluginAPI;

if (!defined('CRISP_COMPONENT')) {
    echo 'Cannot access this component directly!';
    exit;
}


if (!IS_NATIVE_API) {
    PluginAPI::response(Bitmask::GENERIC_ERROR, 'Cannot access non-native API endpoint', [], null, 400);
    exit;
}


$IndexableInterfaces = ['service', 'user', 'case', 'search', 'updatecheck', 'rsa'];
$Interfaces = [];

foreach ($IndexableInterfaces as $interface) {

    $ifacename = $interface;

    if (!file_exists(__DIR__ . '/' . $ifacename)) {
        continue;
    }
    $Interfaces[$ifacename]['_links']['base'] = Config::get('api_cdn') . '/' . $ifacename . '/';

    foreach (new DirectoryIterator(__DIR__ . '/' . $ifacename) as $version) {
        if ($version->isDir() || $version->isDot()) {
            continue;
        }

        $ifaceversion = pathinfo($version->getFilename(), PATHINFO_FILENAME);


        $Interfaces[$ifacename]['_links'][$ifaceversion] = Config::get('api_cdn') . '/' . $ifacename . '/' . $ifaceversion . '/';

        foreach (new DirectoryIterator(__DIR__ . '/' . $ifacename . '/') as $method) {
            if (!$method->isDir() || $method->isDot()) {
                continue;
            }

            $Interfaces[$ifacename]['versions'][$ifaceversion]['methods'][] = pathinfo($method->getFilename(), PATHINFO_FILENAME);
        }
    }

}


PluginAPI::response(Bitmask::REQUEST_SUCCESS, 'Welcome to the ToS;DR API. To access endpoints other than 0x1, define your API Key through the Authorization or x-api-key header. Checkout the docs for usage info.', [
    '_links' => [
        'docs_url' => Config::get('docs_url'),
        'frontpage_url' => Config::get('root_url'),
        'base_url' => Config::get('api_cdn'),
        'shields_url' => Config::get('shield_cdn'),
        'helpdesk_url' => Config::get('support'),
        'phoenix_url' => Config::get('phoenix_url'),
        'forum_url' => Config::get('forum_url'),
        'status_url' => Config::get('status_url')
    ],
    'versions' => [
        'crisp' => core::CRISP_VERSION,
        'api' => core::API_VERSION
    ],
    'rest-interfaces' => $Interfaces
]);