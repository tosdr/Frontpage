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
use crisp\core\Postgres;
use crisp\models\ServiceRatings;

if (!defined('CRISP_COMPONENT')) {
    echo 'Cannot access this component directly!';
    exit;
}

$ID = null;

if (!isset($_GET['service'])) {

    $DB = new Postgres();


    $totalServices = $DB->getDBConnector()->query('SELECT (0) FROM services;')->rowCount();
    $_limit = 100;
    $_pages = ceil($totalServices / $_limit);
    $_offset = ($_pages - 1) * $_limit;


    $_page = min($_pages, filter_var($_GET['page'], FILTER_VALIDATE_INT, [
        'options' => [
            'default' => 1,
            'min_range' => 1
        ]]));


    if ($_page === 1) {
        $_offset = 0;
    }


    $_start = $_offset + 1;
    $_end = min(($_offset + $_limit), $totalServices);


    $ServicesQuery = $DB->getDBConnector()->prepare('SELECT * FROM services LIMIT :limit OFFSET :offset;');
    $ServicesQuery->execute([':limit' => $_limit, ':offset' => $_offset]);
    $Services = $ServicesQuery->fetchAll(PDO::FETCH_ASSOC);

    $ServicesSkel = [];

    foreach ($Services as $index => $service) {

        $newService = [];

        foreach ($service as $key => $value) {
            if (empty($service[$key]) && $service[$key] !== null) {
                $service[$key] = null;
            }
        }


        $service['rating'] = $service['rating'] ?? 'N/A';


        $newService = [
            'id' => $service['id'],
            'is_comprehensively_reviewed' => $service['is_comprehensively_reviewed'],
            'urls' => explode(',', $service['url']),
            'name' => $service['name'],
            'status' => $service['status'],
            'updated_at' => [
                'timezone' => date_default_timezone_get(),
                'pgsql' => $service['updated_at'],
                'unix' => strtotime($service['updated_at']),
            ],
            'created_at' => [
                'timezone' => date_default_timezone_get(),
                'pgsql' => $service['created_at'],
                'unix' => strtotime($service['created_at']),
            ],
            'slug' => $service['slug'],
            'wikipedia' => $service['wikipedia'],
            'rating' => [
                'hex' => ServiceRatings::get($service['rating']),
                'human' => "Grade {$service['rating']}",
                'letter' => $service['rating']
            ],
            'links' => [
                'phoenix' => [
                    'service' => Config::get('phoenix_url') . "/services/{$service['id']}",
                    'documents' => Config::get('phoenix_url') . "/services/{$service['id']}/annotate",
                    'new_comment' => Config::get('phoenix_url') . "/services/{$service['id']}/service_comments/new",
                    'edit' => Config::get('phoenix_url') . "/services/{$service['id']}/edit",
                ],
                'crisp' => [
                    'api' => Config::get('api_cdn') . "/service/v1/?service={$service['id']}",
                    'service' => Config::get('root_url') . "/en/service/{$service['id']}",
                    'badge' => [
                        'svg' => Config::get('shield_cdn') . "/{$service['id']}.svg",
                        'png' => Config::get('shield_cdn') . "/{$service['id']}.png"
                    ],
                ]
            ]
        ];


        $ServicesSkel[] = $newService;
    }

    PluginAPI::response(crisp\core\Bitmask::REQUEST_SUCCESS, 'All services below', [
        '_page' => [
            'total' => $totalServices,
            'current' => $_page,
            'start' => $_start,
            'end' => $_end
        ],
        'services' => $ServicesSkel
    ]);
    exit;
}

if (!is_numeric($_GET['service'])) {
    if (!crisp\api\Phoenix::serviceExistsBySlug($_GET['service'])) {
        PluginAPI::response(Bitmask::INVALID_SERVICE, $_GET['service'], []);
        return;
    }
    $ID = crisp\api\Phoenix::getServiceBySlug($_GET['service'])['id'];
} else {
    $ID = $_GET['service'];
}

if (!crisp\api\Phoenix::serviceExists($ID)) {
    PluginAPI::response(Bitmask::INVALID_SERVICE, $ID, []);
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
        'status' => $Point['status'],
        'analysis' => $Point['analysis'],
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


PluginAPI::response(Bitmask::REQUEST_SUCCESS, 'OK', $SkeletonData);
