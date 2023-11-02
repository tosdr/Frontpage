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
use crisp\api\Phoenix;
use crisp\core\Bitmask;
use crisp\core\PluginAPI;

if(!defined('CRISP_COMPONENT')){
    echo 'Cannot access this component directly!';
    exit;
}


if(!IS_NATIVE_API){
    PluginAPI::response(crisp\core\Bitmask::GENERIC_ERROR, 'Cannot access non-native API endpoint', [], null, 400);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    PluginAPI::response(crisp\core\Bitmask::NOT_IMPLEMENTED, 'Invalid Request Method', [], null, 405);
    exit;
}

if ($this->Query === 'all') {
    $Services = Phoenix::getServices();
    $Response = [
        'version' => time(),
    ];
    foreach ($Services as $Index => $Service) {

        $Service['urls'] = explode(',', $Service['url']);
        $Service['logo'] = Config::get('s3_logos') . '/' . $Service['id'] . '.png';

        $Services[$Index] = $Service;
    }

    $Response['services'] = $Services;

    PluginAPI::response(false, 'All services below', $Response);

    return;
}

if (!is_numeric($this->Query)) {
    if (!crisp\api\Phoenix::serviceExistsBySlug($this->Query)) {
        PluginAPI::response(Bitmask::INVALID_SERVICE + crisp\core\Bitmask::VERSION_DEPRECATED + crisp\core\Bitmask::INTERFACE_DEPRECATED, $this->Query, [], null, 404);
        return;
    }
    $this->Query = crisp\api\Phoenix::getServiceBySlug($this->Query)['id'];
    $SkeletonData = Phoenix::generateApiFiles($this->Query);
    PluginAPI::response(Bitmask::REQUEST_SUCCESS + crisp\core\Bitmask::VERSION_DEPRECATED + crisp\core\Bitmask::INTERFACE_DEPRECATED, $this->Query, Phoenix::generateApiFiles($this->Query, '3'));
    exit;
}

if (!crisp\api\Phoenix::serviceExists($this->Query)) {
    PluginAPI::response(Bitmask::INVALID_SERVICE + crisp\core\Bitmask::VERSION_DEPRECATED + crisp\core\Bitmask::INTERFACE_DEPRECATED, $this->Query, [], null, 404);
    return;
}


PluginAPI::response(Bitmask::REQUEST_SUCCESS + crisp\core\Bitmask::VERSION_DEPRECATED + crisp\core\Bitmask::INTERFACE_DEPRECATED, $this->Query, Phoenix::generateApiFiles($this->Query, '3'));
