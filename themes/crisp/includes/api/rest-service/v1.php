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

if ($_SERVER["REQUEST_METHOD"] === "GET") {

    if (!is_numeric($_GET["service"] ?? $this->Query)) {
        if (!crisp\api\Phoenix::serviceExistsBySlugPG($_GET["service"] ?? $this->Query)) {
            echo \crisp\core\PluginAPI::response(\crisp\core\Bitmask::INVALID_SERVICE, $_GET["service"] ?? $this->Query, []);
            return;
        }
        $_GET["service"] ?? $this->Query = crisp\api\Phoenix::getServiceBySlugPG($_GET["service"] ?? $this->Query)["id"];
        $SkeletonData = \crisp\api\Phoenix::generateApiFiles($_GET["service"] ?? $this->Query);
        echo \crisp\core\PluginAPI::response(\crisp\core\Bitmask::REQUEST_SUCCESS, $_GET["service"] ?? $this->Query, \crisp\api\Phoenix::generateApiFiles($_GET["service"] ?? $this->Query, "3"));
        exit;
    }

    if (!crisp\api\Phoenix::serviceExistsPG($_GET["service"] ?? $this->Query)) {
        echo \crisp\core\PluginAPI::response(\crisp\core\Bitmask::INVALID_SERVICE, $_GET["service"] ?? $this->Query, []);
        return;
    }



    echo \crisp\core\PluginAPI::response(\crisp\core\Bitmask::REQUEST_SUCCESS, $_GET["service"] ?? $this->Query, \crisp\api\Phoenix::generateApiFiles($_GET["service"] ?? $this->Query, "3"));
} else if ($_SERVER["REQUEST_METHOD"] === "POST") {


    echo \crisp\core\PluginAPI::response(crisp\core\Bitmask::NOT_IMPLEMENTED, "Not yet implemented", [], null, 405);
    exit;

    $payload = json_decode(file_get_contents("php://input"));

    if (!isset($payload["name"]) || empty($payload["name"])) {
        echo \crisp\core\PluginAPI::response(crisp\core\Bitmask::MISSING_PARAMETER, "name", []);
        exit;
    }

    if (!isset($payload["documents"]) || empty($payload["documents"])) {
        echo \crisp\core\PluginAPI::response(crisp\core\Bitmask::MISSING_PARAMETER, "documents", []);
        exit;
    }


    if (!is_array($payload["documents"])) {
        echo \crisp\core\PluginAPI::response(crisp\core\Bitmask::INVALID_PARAMETER, "documents is not an array", []);
        exit;
    }
    if (!is_array($payload["domains"])) {
        echo \crisp\core\PluginAPI::response(crisp\core\Bitmask::INVALID_PARAMETER, "domains is not an array", []);
        exit;
    }


    if (count($payload["documents"]) === 0) {
        echo \crisp\core\PluginAPI::response(crisp\core\Bitmask::INVALID_PARAMETER, "documents is an empty array", []);
        exit;
    }

    if (\crisp\api\Phoenix::getServiceByNamePG($payload["name"]) !== false) {
        echo \crisp\core\PluginAPI::response(crisp\core\Bitmask::SERVICE_DUPLICATE, "Service already exists", []);
        exit;
    }


    foreach ($payload["documents"] as $key => $document) {
        if (!isset($document["name"]) || empty($document["name"])) {
            echo \crisp\core\PluginAPI::response(crisp\core\Bitmask::INVALID_PARAMETER, "documents[$key] is missing the name key", []);
            exit;
        }

        if (!isset($document["url"]) || empty($document["url"])) {
            echo \crisp\core\PluginAPI::response(crisp\core\Bitmask::INVALID_PARAMETER, "documents[$key] is missing the url key", []);
            exit;
        }

        // Validations start here //

        if (!preg_match('/^\b(https?):\/\/[\-A-Za-z0-9+&@#\/%?=~_|!:,.;]*[\-A-Za-z0-9+&@#\/%=~_|]\.+[a-z0-9][a-z0-9-]{0,61}[a-z0-9]\/.*$/', $document["url"])) {
            echo \crisp\core\PluginAPI::response(crisp\core\Bitmask::INVALID_PARAMETER, "documents[$key].url is not conform.", []);
            exit;
        }

        // Validations end here //
    }


    foreach ($payload["domains"] as $key => $domain) {
        // Validations start here //

        if (!preg_match('/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z0-9][a-z0-9-]{0,61}[a-z0-9]$/', $domain)) {
            echo \crisp\core\PluginAPI::response(crisp\core\Bitmask::INVALID_PARAMETER, "domain[$key] is not conform.", []);
            exit;
        }

        // Validations end here //
    }

    if (isset($payload["wikipedia"]) && !empty($payload["wikipedia"])) {
        if (!preg_match('^https\:\/\/[a-z]+\.wikipedia\.org\/wiki\/([\w%\-\(\)\.]+)$/', $payload["wikipedia"])) {
            echo \crisp\core\PluginAPI::response(crisp\core\Bitmask::INVALID_PARAMETER, "wikipedia is not conform.", []);
            exit;
        }
    }

    $Postgres = new crisp\core\MySQL();

    $db = $Postgres->getDBConnector()->prepare("INSERT INTO service_requests (name, domains, documents, wikipedia) VALUES (:name, :domains, :documents, :wikipedia)");

    $success = $db->execute(array(
        ":name" => $payload["name"],
        ":domains" => implode(",", $payload["domains"]),
        ":documents" => json_encode($payload["documents"]),
        ":wikipedia" => $payload["wikipedia"]
    ));

    if ($success) {


        $EnvFile = parse_ini_file(__DIR__ . "/../../../.env");

        if ($EnvFile["SERVICE_DISCORD_WEBHOOK"] !== false) {

            $fields = [];

            foreach ($payload["documents"] as $document) {
                $fields[] = array("name" => $document["name"], "value" => $document["url"]);
            }

            $embed = array(
                'content' => 'New Service Request',
                'embeds' =>
                array(
                    array(
                        'title' => $payload["name"],
                        'description' => 'The service request contains ' . count($payload["domains"]) . " domain(s) and " . count($payload["documents"]) . " document(s).",
                        'url' => 'https://tosdr.org/service_requests',
                        'color' => 0,
                        'fields' => $fields,
                        'footer' =>
                        array(
                            'text' => $payload["wikipedia"],
                        ),
                    ),
                ),
            );

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $EnvFile["SERVICE_DISCORD_WEBHOOK"],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($embed),
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json'
                ),
            ));

            $resp = curl_exec($curl);
        }
        echo \crisp\core\PluginAPI::response(crisp\core\Bitmask::REQUEST_SUCCESS, "OK", []);
        exit;
    }

    echo \crisp\core\PluginAPI::response(crisp\core\Bitmask::GENERIC_ERROR, "SQL Error", []);
    exit;
}else{
    echo \crisp\core\PluginAPI::response(crisp\core\Bitmask::NOT_IMPLEMENTED, "Invalid Request Method", [], null, 405);
    exit;
}