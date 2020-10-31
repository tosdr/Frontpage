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

/**
 * JSON API Interface, unused currently
 * @deprecated
 */
class Response {

    function error($http_status, $append = "") {
        header("Content-Type: application/json");



        $json = array("response" => array("status" => $http_status, "message" => $append));

        if (!isset($_GET["readable"])) {
            return json_encode($json);
        }
        return json_encode($json, JSON_PRETTY_PRINT);
    }

    function ok($array_result) {

        header("Content-Type: application/json");

        $json = array("response" => array("status" => 200, "result" => $array_result));
        if (!isset($_GET["readable"])) {
            return json_encode($json);
        }
        return json_encode($json, JSON_PRETTY_PRINT);
    }

}
