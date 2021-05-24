<?php /** @noinspection ALL */

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

use crisp\api\Elastic;
use crisp\core\Bitmask;
use crisp\core\PluginAPI;

if (!defined('CRISP_COMPONENT')) {
    echo 'Cannot access this component directly!';
    exit;
}

$inputQuery = null;
$inputQuery = $_GET['query'] ?? $inputQuery;
$ES = new Elastic();

if (!$inputQuery) {
    PluginAPI::response(Bitmask::QUERY_FAILED, 'Missing query', [
        'services' => []
    ]);
    exit;
}

try {
    $services = $ES->search($inputQuery);

    $serviceResults = [];

    foreach ($services->hits as $Hit) {
        $service = [
            "id" => $Hit->_source->id,
            "is_comprehensively_reviewed" => $Hit->_source->is_comprehensively_reviewed,
            "urls" => explode(",", $Hit->_source->url),
            "name" => $Hit->_source->name,
            "status" => $Hit->_source->status,
            "updated_at" => $Hit->_source->updated_at,
            "created_at" => $Hit->_source->created_at,
            "slug" => $Hit->_source->slug,
            "wikipedia" => $Hit->_source->wikipedia,
            "rating" => [
                "hex" => \crisp\models\ServiceRatings::get($Hit->_source->rating),
                "human" => "Grade {$Hit->_source->rating}",
                "letter" => $Hit->_source->rating
            ],
            "links" => [
                "phoenix" => [
                    "service" => \crisp\api\Config::get("phoenix_url") . "/services/{$Hit->_source->id}",
                    "documents" => \crisp\api\Config::get("phoenix_url") . "/services/{$Hit->_source->id}/annotate",
                    "new_comment" => \crisp\api\Config::get("phoenix_url") . "/services/{$Hit->_source->id}/service_comments/new",
                    "edit" => \crisp\api\Config::get("phoenix_url") . "/services/{$Hit->_source->id}/edit",
                ],
                "crisp" => [
                    "api" => \crisp\api\Config::get("api_cdn") . "/rest-service/v3/{$Hit->_source->id}.json",
                    "service" => \crisp\api\Config::get("root_url") . "/en/service/{$Hit->_source->id}",
                    "badge" => [
                        "svg" => \crisp\api\Config::get("shield_cdn") . "/{$Hit->_source->id}.svg",
                        "png" => \crisp\api\Config::get("shield_cdn") . "/{$Hit->_source->id}.png"
                    ],
                ]
            ]
        ];
        $serviceResults[] = $service;
    }

    PluginAPI::response(Bitmask::REQUEST_SUCCESS, $inputQuery, [
        'services' => $serviceResults
    ]);

} catch (Throwable $e) {
    PluginAPI::response(Bitmask::QUERY_FAILED, 'Internal Server Error', [
        'services' => []
    ]);
}