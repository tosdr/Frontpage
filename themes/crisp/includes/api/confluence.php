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

if (crisp\api\Helper::getRealIpAddr() !== "202.61.251.191") {
    echo \crisp\core\PluginAPI::response(crisp\core\Bitmask::INVALID_SUBNET, "IP not whitelisted", [], null, 401);
    exit;
}

$data = json_decode(file_get_contents('php://input'));

if (!$data) {
    echo \crisp\core\PluginAPI::response(crisp\core\Bitmask::INVALID_PARAMETER, "Missing data", [], null, 400);
    exit;
}


if ($data->test) {
    echo \crisp\core\PluginAPI::response(crisp\core\Bitmask::REQUEST_SUCCESS, "OK", [], null);
    exit;
}

if (!$data->event == "page_updated" || !$data->event == "page_created" || !$data->event == "blog_created" || !$data->event == "blog_updated") {
    echo \crisp\core\PluginAPI::response(crisp\core\Bitmask::INVALID_PARAMETER, "Invalid Event", [], null, 400);
    exit;
}

$confluencePage = file_get_contents("https://docs.tosdr.org/rest/api/content/" . $data->page->id);
#$confluencePage = json_decode(file_get_contents("https://docs.tosdr.org/rest/api/content/360496"));




$curlconfluence = curl_init();
curl_setopt_array($curlconfluence, array(
    CURLOPT_URL => "https://docs.tosdr.org/rest/api/content/" . $data->page->id,
    CURLOPT_RETURNTRANSFER => true,
));

$confluencePage = json_decode(curl_exec($curlconfluence));
curl_close($curlconfluence);

if(!$confluencePage ||  $confluencePage === null || $confluencePage->statusCode == 404){    
    echo \crisp\core\PluginAPI::response(crisp\core\Bitmask::GENERIC_ERROR, "Invalid Confluence response", [], null, 400);
    exit;
}


$embed = array(
    'content' => 'The docs have been updated',
    'embeds' =>
    array(
        array(
            'title' => $confluencePage->title,
            'description' => $confluencePage->version->by->displayName . ' has updated the ' . $confluencePage->space->name . ' docs.',
            'url' => 'https://docs.tosdr.org/pages/viewpage.action?pageId=' . $confluencePage->id,
            'color' => 0,
            'footer' =>
            array(
                'text' => 'Confluence',
            ),
        ),
    ),
);

$curl = curl_init();
$EnvFile = parse_ini_file(__DIR__ . "/../../../../.env");

if (!$EnvFile["CONFLUENCE_DISCORD_WEBHOOK"]) {
    echo \crisp\core\PluginAPI::response(crisp\core\Bitmask::GENERIC_ERROR, "Webhook not set", [], null, 500);
    exit;
}

curl_setopt_array($curl, array(
    CURLOPT_URL => $EnvFile["CONFLUENCE_DISCORD_WEBHOOK"],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS => json_encode($embed),
    CURLOPT_HTTPHEADER => array(
        'Content-Type: application/json'
    ),
));

$resp = curl_exec($curl);

if (!$resp) {
    echo \crisp\core\PluginAPI::response(crisp\core\Bitmask::GENERIC_ERROR, curl_errno($curl), [], null, 502);
}

curl_close($curl);
