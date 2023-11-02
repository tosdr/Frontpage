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
$Skeleton = [];


/**
 * @throws JsonException
 */
function getReleases(string $Repository): ?array
{
    $Release = [];

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => "https://api.github.com/repos/$Repository/releases",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/x-www-form-urlencoded',
            'User-Agent: CrispCMS'
        ],
    ]);
    $response = curl_exec($curl);

    if (curl_getinfo($curl, CURLINFO_RESPONSE_CODE) !== 200) {
        return null;
    }

    $api = json_decode($response, false, 512, JSON_THROW_ON_ERROR);

    $Latest = getLatestRelease($Repository);


    if ($Latest === null) {
        $Release['latest'] = null;
    } else {

        $Release['latest'] = [
            'version' => $Latest->tag_name,
            'beta' => $Latest->prerelease,
            'branch' => $Latest->target_commitish,
            'created_at' => $Latest->created_at,
            'published_at' => $Latest->published_at,
            'source' => $Latest->zipball_url
        ];

    }

    foreach($api as $releaseItem){
        $Release[$releaseItem->tag_name] = [
            'version' => $releaseItem->tag_name,
            'beta' => $releaseItem->prerelease,
            'branch' => $releaseItem->target_commitish,
            'created_at' => $releaseItem->created_at,
            'published_at' => $releaseItem->published_at,
            'source' => $releaseItem->zipball_url
        ];
    }


    return $Release;
}

/**
 * @throws JsonException
 */
function getLatestRelease(string $Repository): ?object
{

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => "https://api.github.com/repos/$Repository/releases/latest",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/x-www-form-urlencoded',
            'User-Agent: CrispCMS'
        ],
    ]);
    $response = curl_exec($curl);

    if (curl_getinfo($curl, CURLINFO_RESPONSE_CODE) !== 200) {
        return null;
    }

    return json_decode($response, false, 512, JSON_THROW_ON_ERROR);
}


$Repos = Config::get('repositories');

foreach($Repos as $Repo){
    $Skeleton[$Repo] = getReleases($Repo);
}



PluginAPI::response(Bitmask::REQUEST_SUCCESS, 'OK', $Skeleton);
