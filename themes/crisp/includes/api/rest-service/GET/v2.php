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
use crisp\core\Bitmask;
use crisp\core\PluginAPI;
use crisp\models\ServiceRatings;

if (!defined('CRISP_COMPONENT')) {
    echo 'Cannot access this component directly!';
    exit;
}

$ID = null;

if (!is_numeric($_GET['service'] ?? $this->Query)) {
    if (!crisp\api\Phoenix::serviceExistsBySlug($_GET['service'] ?? $this->Query)) {
        PluginAPI::response(Bitmask::INVALID_SERVICE + Bitmask::INTERFACE_DEPRECATED + Bitmask::VERSION_DEPRECATED, $_GET['service'] ?? $this->Query, []);
        return;
    }
    $ID = crisp\api\Phoenix::getServiceBySlug($_GET['service'] ?? $this->Query)['id'];
} else {
    $ID = $_GET['service'] ?? $this->Query;
}

if (!crisp\api\Phoenix::serviceExists($ID)) {
    PluginAPI::response(Bitmask::INVALID_SERVICE + Bitmask::INTERFACE_DEPRECATED + Bitmask::VERSION_DEPRECATED, $ID, []);
    return;
}


$ServiceLinks = [];
$ServicePoints = [];
$ServicePointsData = [];

$points = crisp\api\Phoenix::getPointsByService($ID);
$service = crisp\api\Phoenix::getService($ID);
$documents = crisp\api\Phoenix::getDocumentsByService($ID);

$_service = [
    'id' => $service['_source']['id'],
    'name' => $service['_source']['name'],
    'created_at' => $service['_source']['created_at'],
    'updated_at' => $service['_source']['updated_at'],
    'wikipedia' => (trim($service['_source']['wikipedia']) === '' ? null : $service['_source']['wikipedia']),
    'keywords' => $service['_source']['keywords'],
    'related' => $service['_source']['related'],
    'slug' => $service['_source']['slug'],
    'is_comprehensively_reviewed' => $service['_source']['is_comprehensively_reviewed'],
    'rating' => ServiceRatings::get($service['_source']['rating']),
    'status' => $service['_source']['status'],
    'image' => $service['_source']['image'],
    'url' => $service['_source']['url'],
];
$_documents = [];

foreach ($documents as $Document) {
    $_documents[] = [
        'id' => $Document['id'],
        'name' => $Document['name'],
        'url' => $Document['url'],
        'xpath' => $Document['xpath'],
        'text' => $Document['text'],
        'created_at' => $Document['created_at'],
        'updated_at' => $Document['updated_at'],
    ];
}

foreach ($points as $Point) {
    $_Point = [
        'id' => $Point['id'],
        'title' => $Point['title'],
        'source' => $Point['source'],
        'status' => $Point['analysis'],
        'created_at' => $Point['created_at'],
        'updated_at' => $Point['updated_at'],
        'quoteText' => $Point['quoteText'],
        'case_id' => $Point['case_id'],
        'document_id' => $Point['document_id'],
        'quoteStart' => $Point['quoteStart'],
        'quoteEnd' => $Point['quoteEnd'],
    ];

    $Document = array_column($_documents, null, 'id')[$Point['document_id']];
    $Case = crisp\api\Phoenix::getCase($Point['case_id']);
    $ServicePointsData[] = $_Point;
}

$SkeletonData = $_service;

$SkeletonData['image'] = Config::get('s3_logos') . '/' . $_service['image'];
$SkeletonData['documents'] = $_documents;
$SkeletonData['points'] = $ServicePointsData;
$SkeletonData['urls'] = explode(',', $_service['url']);


PluginAPI::response(Bitmask::REQUEST_SUCCESS + Bitmask::INTERFACE_DEPRECATED + Bitmask::VERSION_DEPRECATED, 'OK', $SkeletonData);
