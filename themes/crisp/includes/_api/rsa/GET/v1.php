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
use crisp\api\REST;
use crisp\core\Bitmask;
use crisp\core\PluginAPI;
use crisp\core\Postgres;
use crisp\models\CaseClassifications;

if (!defined('CRISP_COMPONENT')) {
    echo 'Cannot access this component directly!';
    exit;
}

$EnvFile = parse_ini_file(__DIR__ . '/../../../../../../.env');

$KeyMappings = ['RSA_APIKEY_PRIVATE' => 'apikey'];


$Data = [];

if (isset($_GET['key'])) {
    $Index = array_search($_GET['key'], $KeyMappings, true);

    if (!$Index && $EnvFile[$Index] !== null) {
        REST::response(crisp\core\Bitmask::INVALID_PARAMETER, 'Invalid RSA Key');
        exit;
    }

    $PkData = file_get_contents($EnvFile[$Index]);

    if (!$PkData) {
        REST::response(crisp\core\Bitmask::GENERIC_ERROR, 'Failed to read RSA Key');
        exit;
    }
    $Pk = openssl_pkey_get_private($PkData);
    $PkDetails = openssl_pkey_get_details($Pk);

    $Data = [
        'key' => $PkDetails['key'],
        'bits' => $PkDetails['bits'],
    ];

} else {
    foreach ($KeyMappings as $Key => $Value) {

        $PkData = file_get_contents($EnvFile[$Key]);

        if (!$PkData) {
            REST::response(crisp\core\Bitmask::GENERIC_ERROR, 'Failed to read RSA Key');
            exit;
        }

        $Pk = openssl_pkey_get_private($PkData);
        $PkDetails = openssl_pkey_get_details($Pk);

        $Data[$Value] = [
            'key' => $PkDetails['key'],
            'bits' => $PkDetails['bits'],
        ];
    }
}


REST::response(crisp\core\Bitmask::REQUEST_SUCCESS, 'RSA below', $Data);