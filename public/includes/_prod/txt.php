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

use crisp\api\Translation;
use crisp\exceptions\BitmaskException;

if(!defined('CRISP_COMPONENT')){
    echo 'Cannot access this component directly!';
    exit;
}


function getLineWithString($array, $str): int|string
{
    foreach ($array as $lineNumber => $line) {
        if (str_contains($line, $str)) {
            return $lineNumber;
        }
    }
    return -1;
}

if (isset($_POST['domain'])) {
    if (empty($_POST['domain'])) {
        crisp\core\PluginAPI::response(crisp\core\Bitmask::QUERY_FAILED, Translation::fetch('views.txt.errors.no_domain'));
        exit;
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $_POST['domain'] . '/tosdr.txt');
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    $txtFile = curl_exec($ch);
    $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if (!$txtFile) {
        crisp\core\PluginAPI::response(crisp\core\Bitmask::QUERY_FAILED, Translation::fetch('views.txt.errors.curl_error', 1, ['{{ path }}' => 'https://' . $_POST['domain'] . '/tosdr.txt']));
        exit;
    }

    if ($http_status === 404) {
        crisp\core\PluginAPI::response(crisp\core\Bitmask::QUERY_FAILED, Translation::fetch('views.txt.errors.not_found', 1, ['{{ path }}' => 'https://' . $_POST['domain'] . '/tosdr.txt']));
        exit;
    }
    if ($http_status !== 200) {
        crisp\core\PluginAPI::response(crisp\core\Bitmask::QUERY_FAILED, Translation::fetch('views.txt.errors.non_success', 1, ['{{ path }}' => 'https://' . $_POST['domain'] . '/tosdr.txt']));
        exit;
    }
    try {

        $parsed = crisp\core\Txt::parse($txtFile, $_POST['domain']);
    } catch (BitmaskException $ex) {
        crisp\core\PluginAPI::response($ex->getCode(), $ex->getMessage());
        exit;
    }

    crisp\core\PluginAPI::response(crisp\core\Bitmask::REQUEST_SUCCESS, 'OK', [
        'results' => var_export($parsed['results'], true),
        'failed_validations' => var_export($parsed['failed_validations'], true)
    ]);
    exit;
}