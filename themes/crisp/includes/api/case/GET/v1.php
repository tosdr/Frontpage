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
use crisp\models\CaseClassifications;

if (!defined('CRISP_COMPONENT')) {
    echo 'Cannot access this component directly!';
    exit;
}


if (isset($_GET['case'])) {

    if (!crisp\api\Phoenix::getCase($_GET['case'])) {
        PluginAPI::response(Bitmask::INVALID_CASE, $_GET['case'], []);
        exit;
    }

    $case = Phoenix::getCase($_GET['case']);


    foreach ($case as $key => $value) {
        if (empty($case[$key]) && $case[$key] !== null) {
            $case[$key] = null;
        }
    }


    $newCase = [
        'id' => $case['id'],
        'weight' => $case['score'],
        'title' => $case['title'],
        'description' => $case['description'],
        'updated_at' => [
            'timezone' => date_default_timezone_get(),
            'pgsql' => $case['updated_at'],
            'unix' => strtotime($case['updated_at']),
        ],
        'created_at' => [
            'timezone' => date_default_timezone_get(),
            'pgsql' => $case['created_at'],
            'unix' => strtotime($case['created_at']),
        ],
        'topic' => $case['topic_id'],
        'classification' => [
            'hex' => CaseClassifications::get($case['classification']),
            'human' => $case['classification']
        ],
        'links' => [
            'phoenix' => [
                'case' => Config::get('phoenix_url') . "/cases/{$case['id']}",
                'new_comment' => Config::get('phoenix_url') . "/cases/{$case['id']}/case_comments/new",
                'edit' => Config::get('phoenix_url') . "/cases/{$case['id']}/edit",
            ],
            'crisp' => [
                'api' => Config::get('api_cdn') . "/case/v1/?case={$case['id']}",
            ]
        ]
    ];

    PluginAPI::response(Bitmask::REQUEST_SUCCESS, $_GET['case'], $newCase);
    exit;
}

$DB = new Postgres();


$totalCases = $DB->getDBConnector()->query('SELECT (0) FROM cases;')->rowCount();
$_limit = 100;
$_pages = ceil($totalCases / $_limit);
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
$_end = min(($_offset + $_limit), $totalCases);


$ServicesQuery = $DB->getDBConnector()->prepare('SELECT * FROM cases LIMIT :limit OFFSET :offset;');
$ServicesQuery->execute([':limit' => $_limit, ':offset' => $_offset]);
$Cases = $ServicesQuery->fetchAll(PDO::FETCH_ASSOC);


$CasesSkel = [];


foreach ($Cases as $index => $case) {

    $newCase = [];

    foreach ($case as $key => $value) {
        if (empty($case[$key]) && $case[$key] !== null) {
            $case[$key] = null;
        }

    }


    $newCase = [
        'id' => $case['id'],
        'weight' => $case['score'],
        'title' => $case['title'],
        'description' => $case['description'],
        'updated_at' => [
            'timezone' => date_default_timezone_get(),
            'pgsql' => $case['updated_at'],
            'unix' => strtotime($case['updated_at']),
        ],
        'created_at' => [
            'timezone' => date_default_timezone_get(),
            'pgsql' => $case['created_at'],
            'unix' => strtotime($case['created_at']),
        ],
        'topic' => $case['topic_id'],
        'classification' => [
            'hex' => CaseClassifications::get($case['classification']),
            'human' => $case['classification']
        ],
        'links' => [
            'phoenix' => [
                'case' => Config::get('phoenix_url') . "/cases/{$case['id']}",
                'new_comment' => Config::get('phoenix_url') . "/cases/{$case['id']}/case_comments/new",
                'edit' => Config::get('phoenix_url') . "/cases/{$case['id']}/edit",
            ],
            'crisp' => [
                'api' => Config::get('api_cdn') . "/case/v1/?case={$case['id']}",
            ]
        ]
    ];


    $CasesSkel[] = $newCase;
}

PluginAPI::response(crisp\core\Bitmask::REQUEST_SUCCESS, 'All cases below', [
    '_page' => [
        'total' => $totalCases,
        'current' => $_page,
        'start' => $_start,
        'end' => $_end
    ],
    'cases' => $CasesSkel
]);