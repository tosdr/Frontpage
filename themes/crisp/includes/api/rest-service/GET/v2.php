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

$ID;

if (!is_numeric($_GET["service"] ?? $this->Query)) {
    if (!crisp\api\Phoenix::serviceExistsBySlugPG($_GET["service"] ?? $this->Query)) {
        echo \crisp\core\PluginAPI::response(\crisp\core\Bitmask::INVALID_SERVICE, $_GET["service"] ?? $this->Query, []);
        return;
    }
    $ID = crisp\api\Phoenix::getServiceBySlugPG($_GET["service"] ?? $this->Query)["id"];
} else {
    $ID = $_GET["service"] ?? $this->Query;
}

if (!crisp\api\Phoenix::serviceExistsPG($ID)) {
    echo \crisp\core\PluginAPI::response(\crisp\core\Bitmask::INVALID_SERVICE, $ID, []);
    return;
}


$ServiceLinks = array();
$ServicePoints = array();
$ServicePointsData = array();

$points = crisp\api\Phoenix::getPointsByServicePG($ID);
$service = crisp\api\Phoenix::getServicePG($ID);
$documents = crisp\api\Phoenix::getDocumentsByServicePG($ID);

$_service = [
    "id" => $service["_source"]["id"],
    "name" => $service["_source"]["name"],
    "created_at" => $service["_source"]["created_at"],
    "updated_at" => $service["_source"]["updated_at"],
    "wikipedia" => (trim($service["_source"]["wikipedia"]) === "" ? null : $service["_source"]["wikipedia"]),
    "keywords" => $service["_source"]["keywords"],
    "related" => $service["_source"]["related"],
    "slug" => $service["_source"]["slug"],
    "is_comprehensively_reviewed" => $service["_source"]["is_comprehensively_reviewed"],
    "rating" => \crisp\models\ServiceRatings::get($service["_source"]["rating"]),
    "status" => $service["_source"]["status"],
    "image" => $service["_source"]["image"],
    "url" => $service["_source"]["url"],
];
$_documents = [];

foreach ($documents as $Document) {
    $_documents[] = [
        "id" => $Document["id"],
        "name" => $Document["name"],
        "url" => $Document["url"],
        "xpath" => $Document["xpath"],
        "text" => $Document["text"],
        "created_at" => $Document["created_at"],
        "updated_at" => $Document["updated_at"],
    ];
}

foreach ($points as $Point) {
    $_Point = [
        "id" => $Point["id"],
        "title" => $Point["title"],
        "source" => $Point["source"],
        "status" => $Point["analysis"],
        "created_at" => $Point["created_at"],
        "updated_at" => $Point["updated_at"],
        "quoteText" => $Point["quoteText"],
        "case_id" => $Point["case_id"],
        "document_id" => $Point["document_id"],
        "quoteStart" => $Point["quoteStart"],
        "quoteEnd" => $Point["quoteEnd"],
    ];

    $Document = array_column($_documents, null, 'id')[$Point["document_id"]];
    $Case = crisp\api\Phoenix::getCasePG($Point["case_id"]);
    $ServicePointsData[] = $_Point;
}

$SkeletonData = $_service;

$SkeletonData["image"] = \crisp\api\Config::get("s3_logos") . "/" . $_service["image"];
$SkeletonData["documents"] = $_documents;
$SkeletonData["points"] = $ServicePointsData;
$SkeletonData["urls"] = explode(",", $_service["url"]);


echo \crisp\core\PluginAPI::response(\crisp\core\Bitmask::REQUEST_SUCCESS, "OK", $SkeletonData);
