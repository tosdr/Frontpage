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

if (isset($_POST["payload"]) || !empty($_POST["payload"])) {
    $payload = $_POST["payload"];

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

    if (isset($payload["email"]) && !empty($payload["email"])) {
        if (!preg_match('/^[^\s@]+@[^\s@]+$/', $payload["email"])) {
            echo \crisp\core\PluginAPI::response(crisp\core\Bitmask::INVALID_PARAMETER, "email is not conform.", []);
            exit;
        }
    }


    $Postgres = new crisp\core\MySQL();

    $duplicatereq = $Postgres->getDBConnector()->prepare("SELECT * FROM service_requests WHERE name = :name OR domains = :domains");
    $duplicatereq->execute([
        ":name" => $payload["name"],
        ":domains" => implode(",", $payload["domains"])
    ]);

    if ($duplicatereq->rowCount() > 0) {
        echo \crisp\core\PluginAPI::response(crisp\core\Bitmask::SERVICE_DUPLICATE, "Service Request already exists", []);
        exit;
    }


    $db = $Postgres->getDBConnector()->prepare("INSERT INTO service_requests (name, domains, documents, wikipedia, email, note) VALUES (:name, :domains, :documents, :wikipedia, :email, :note)");

    $success = $db->execute(array(
        ":name" => $payload["name"],
        ":note" => $payload["note"],
        ":domains" => implode(",", $payload["domains"]),
        ":documents" => json_encode($payload["documents"]),
        ":wikipedia" => $payload["wikipedia"],
        ":email" => $payload["email"]
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
}