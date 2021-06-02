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

namespace crisp\api;

use crisp\core\Bitmask;
use crisp\exceptions\BitmaskException;
use JsonException;
use stdClass;

class Elastic {

    private string $Elastic_URI;
    private string $Elastic_Index;

    public function __construct() {
        $EnvFile = parse_ini_file(__DIR__ . '/../../../../.env');
        $this->Elastic_URI = $EnvFile['ELASTIC_URI'];
        $this->Elastic_Index = $EnvFile['ELASTIC_INDEX'];
    }

    /**
     * @param string $Query
     * @return stdClass
     * @throws BitmaskException
     * @throws JsonException
     */
    public function search(string $Query): stdClass {
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $this->Elastic_URI . '/' . $this->Elastic_Index . '/_search',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode([
                'query' => [
                    'query_string' => [
                        'fields' => ['name', 'slug'],
                        'query' => "*$Query*"
                    ]
                ],
                'size' => 25,
                'from' => 0,
                'sort' => ['id']
            ], JSON_THROW_ON_ERROR),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json'
            ],
        ]);

        $output = curl_exec($ch);
        if (str_starts_with(curl_getinfo($ch, CURLINFO_HTTP_CODE), '5')) {
            throw new BitmaskException(curl_getinfo($ch, CURLINFO_HTTP_CODE), Bitmask::ELASTIC_CONN_ERROR);
        }
        if (curl_getinfo($ch, CURLINFO_HTTP_CODE) !== 200) {
            throw new BitmaskException(curl_getinfo($ch, CURLINFO_HTTP_CODE), Bitmask::ELASTIC_QUERY_MALFORMED);
        }
        if (!$output) {
            throw new BitmaskException(curl_error($ch), Bitmask::ELASTIC_CONN_ERROR);
        }
        curl_close($ch);
        return json_decode($output, false, 512, JSON_THROW_ON_ERROR)->hits;
    }

}
