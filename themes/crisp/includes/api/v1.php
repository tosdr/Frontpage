<?php

/* 
 * Copyright (C) 2021 Justin René Back <justin@tosdr.org>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

use crisp\api\Config;
use crisp\api\Phoenix;
use crisp\core\Bitmask;
use crisp\core\PluginAPI;

if(!defined('CRISP_COMPONENT')){
    echo 'Cannot access this component directly!';
    exit;
}


header('Content-Type: application/json');

if ($this->Query === 'all') {
    $Services = Phoenix::getServices();
    $Response = array(
        'tosdr/api/version' => 1,
        'tosdr/data/version' => time(),
    );
    foreach ($Services as $Service) {
        $URLS = explode(',', $Service['url']);
        foreach ($URLS as $URL) {
            $URL = trim($URL);
            $Response["tosdr/review/$URL"] = array(
                'id' => (int) $Service['id'],
                'documents' => [],
                'logo' => Config::get('s3_logos') . '/' . $Service['id'] . '.png',
                'name' => $Service['name'],
                'slug' => $Service['slug'],
                'rated' => ($Service['rating'] === 'N/A' ? false : ($Service['is_comprehensively_reviewed'] ? $Service['rating'] : false)),
                'points' => []
            );
        }
    }
    echo json_encode($Response, JSON_THROW_ON_ERROR);
    return;
}

if (!is_numeric($this->Query)) {
    if (!crisp\api\Phoenix::serviceExistsBySlug($this->Query)) {
        PluginAPI::response(Bitmask::INVALID_SERVICE, $this->Query, [], null, 404);
        return;
    }
    $this->Query = crisp\api\Phoenix::getServiceBySlug($this->Query)['id'];
    
    $SkeletonData = Phoenix::generateApiFiles($this->Query);
    echo json_encode($SkeletonData, JSON_THROW_ON_ERROR);


    exit;
}



if (!crisp\api\Phoenix::serviceExists($this->Query)) {
    PluginAPI::response(Bitmask::INVALID_SERVICE, $this->Query, [], null, 404);
    return;
}

$SkeletonData = Phoenix::generateApiFiles($this->Query);

echo json_encode($SkeletonData, JSON_THROW_ON_ERROR);
