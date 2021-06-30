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

$opts = [
    'http' => [
        'method' => 'GET',
        'header' => "User-Agent: ToS;DR\r\n"
    ]
];

$context = stream_context_create($opts);

$Response = json_decode(file_get_contents('https://api.github.com/repos/tosdr/browser-extensions/releases/latest', false, $context), false, 512, JSON_THROW_ON_ERROR);

if (!isset($this->Query) || empty($this->Query)) {
    PluginAPI::response(Bitmask::NONE, 'Latest GitHub Release', ['release' => $Response->tag_name]);
    exit;
}

$Version = $this->Query;
$Latest = $Response->tag_name;
if (str_starts_with($this->Query, 'v')) {
    $Version = substr($Version, 1);
}
if (str_starts_with($Latest, 'v')) {
    $Latest = substr($Latest, 1);
}

PluginAPI::response(Bitmask::NONE, 'Comparing versions', ['latest' => $Latest, 'given' => $Version, 'substring' => str_starts_with($this->Query, 'v'), 'compare' => version_compare($Latest, $Version)]);