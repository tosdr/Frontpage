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

/** @var \crisp\core\PluginAPI $this */
/** @var \crisp\core\PluginAPI self */
$EnvFile = parse_ini_file(__DIR__ . "/../../.env");
$Error = false;
$Message = "";


if (empty($EnvFile["DISCOURSE_WEBHOOK_SECRET"])) {
    $Error[] = "DISCOURSE_WEBHOOK_SECRET is not set in .env file";
}


if ($_GET["q"] == $EnvFile["DISCOURSE_WEBHOOK_SECRET"]) {
    $JSON = json_decode(file_get_contents('php://input'), true);
    $Service = \crisp\api\Phoenix::getServiceByNamePG($JSON["service_name"]);

    if (!$Service) {
        $Error[] = "DUPLICATE";
        file_get_contents("https://webhook.site/079dfdd7-2620-46fa-ab03-9ab3c74b1110?duplicate");
    } elseif ($JSON !== null) {


        $ServiceName = $JSON["service_name"];
        //$Documents = explode("\n", $JSON["documents"]);
        $Domains = $JSON["domains"];
        $Wikipedia = $JSON["wikipedia"];

        $Postgres = new \crisp\core\Postgres();

        /** @var \PDO $Database */
        $Database = $Postgres->getDBConnector();

        if (strpos("http://", $Database) !== false || strpos("https://", $Database) !== false) {
            $Error[] = "INVALID_DOMAIN";
            file_get_contents("https://webhook.site/079dfdd7-2620-46fa-ab03-9ab3c74b1110?invalid_domain");
        } else {

        }
    }
}


$this->response($Error, $Message, []);
