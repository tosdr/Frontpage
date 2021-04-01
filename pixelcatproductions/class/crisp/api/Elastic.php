<?php

/*
 * Copyright 2020 Pixelcat Productions <support@pixelcatproductions.net>
 * @author 2020 Justin René Back <jback@pixelcatproductions.net>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace crisp\api;

class Elastic {

    private $Elastic_URI;
    private $Elastic_Index;

    public function __construct() {
        $EnvFile = parse_ini_file(__DIR__ . "/../../../../.env");
        $this->Elastic_URI = $EnvFile["ELASTIC_URI"];
        $this->Elastic_Index = $EnvFile["ELASTIC_INDEX"];
    }

    public function search(string $Query) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->Elastic_URI . "/" . $this->Elastic_Index . "/_search?q=*" . urlencode($Query) . "*");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);
        curl_close($ch);
        return json_decode($output)->hits;
    }

}