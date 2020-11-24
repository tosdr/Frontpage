<?php

/*
 * Copyright (C) 2020 Justin Back <jback@pixelcatproductions.net>
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

/**
 * Some useful helper functions
 */
class Phoenix {

    private static ?\Redis $Redis_Database_Connection = null;

    public function __construct() {
        self::initDB();
    }

    private static function initDB() {
        $DB = new \crisp\core\Redis();
        self::$Redis_Database_Connection = $DB->getDBConnector();
    }

    public static function getPoint($ID, $Force = false) {
        if (self::$Redis_Database_Connection === null) {
            self::initDB();
        }

        if (self::$Redis_Database_Connection->exists(Config::get("phoenix_api_endpoint") . "/points/id/$ID") && !$Force) {
            return json_decode(self::$Redis_Database_Connection->get(Config::get("phoenix_api_endpoint") . "/points/id/$ID"));
        }

        $curl = \curl_init();
        \curl_setopt_array($curl, array(
            CURLOPT_URL => Config::get("phoenix_url") . Config::get("phoenix_api_endpoint") . "/points/$ID",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_USERAGENT => "CrispCMS ToS;DR",
        ));
        $raw = \curl_exec($curl);
        $response = json_decode($raw);

        if ($response === null) {
            throw new \Exception("Failed to crawl! " . $raw);
        }
        if ($response->error) {
            throw new \Exception($response->error);
        }


        if (self::$Redis_Database_Connection->set(Config::get("phoenix_api_endpoint") . "/points/id/$ID", json_encode($response), 2592000)) {
            return $response;
        }
        throw new \Exception("Failed to contact REDIS");
    }

    public static function getCase($ID, $Force = false) {
        if (self::$Redis_Database_Connection === null) {
            self::initDB();
        }

        if (self::$Redis_Database_Connection->exists(Config::get("phoenix_api_endpoint") . "/cases/id/$ID") && !$Force) {
            return json_decode(self::$Redis_Database_Connection->get(Config::get("phoenix_api_endpoint") . "/cases/id/$ID"));
        }

        $curl = \curl_init();
        \curl_setopt_array($curl, array(
            CURLOPT_URL => Config::get("phoenix_url") . Config::get("phoenix_api_endpoint") . "/cases/$ID",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_USERAGENT => "CrispCMS ToS;DR",
        ));
        $raw = \curl_exec($curl);
        $response = json_decode($raw);

        if ($response === null) {
            throw new \Exception("Failed to crawl! " . $raw);
        }
        if ($response->error) {
            throw new \Exception($response->error);
        }


        if (self::$Redis_Database_Connection->set(Config::get("phoenix_api_endpoint") . "/cases/id/$ID", json_encode($response), 2592000)) {
            return $response;
        }
        throw new \Exception("Failed to contact REDIS");
    }

    public static function getTopic($ID, $Force = false) {
        if (self::$Redis_Database_Connection === null) {
            self::initDB();
        }

        if (self::$Redis_Database_Connection->exists(Config::get("phoenix_api_endpoint") . "/topics/id/$ID") && !$Force) {
            return json_decode(self::$Redis_Database_Connection->get(Config::get("phoenix_api_endpoint") . "/topics/id/$ID"));
        }

        $curl = \curl_init();
        \curl_setopt_array($curl, array(
            CURLOPT_URL => Config::get("phoenix_url") . Config::get("phoenix_api_endpoint") . "/topics/$ID",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_USERAGENT => "CrispCMS ToS;DR",
        ));
        $raw = \curl_exec($curl);
        $response = json_decode($raw);

        if ($response === null) {
            throw new \Exception("Failed to crawl! " . $raw);
        }
        if ($response->error) {
            throw new \Exception($response->error);
        }


        if (self::$Redis_Database_Connection->set(Config::get("phoenix_api_endpoint") . "/topics/id/$ID", json_encode($response), 2592000)) {
            return $response;
        }
        throw new \Exception("Failed to contact REDIS");
    }

    public static function getServiceByName($Name, $Force = false) {
        $Name = strtolower($Name);
        if (self::$Redis_Database_Connection === null) {
            self::initDB();
        }

        if (self::$Redis_Database_Connection->exists(Config::get("phoenix_api_endpoint") . "/services/name/$Name") && !$Force) {
            return json_decode(self::$Redis_Database_Connection->get(Config::get("phoenix_api_endpoint") . "/services/name/$Name"));
        }
        throw new Exception("Service is not initialized!");
    }

    public static function serviceExistsByName($Name) {
        $Name = strtolower($Name);

        if (self::$Redis_Database_Connection === null) {
            self::initDB();
        }

        return self::$Redis_Database_Connection->exists(Config::get("phoenix_api_endpoint") . "/services/name/$Name");
    }

    public static function pointExists($ID) {
        if (self::$Redis_Database_Connection === null) {
            self::initDB();
        }

        return self::$Redis_Database_Connection->exists(Config::get("phoenix_api_endpoint") . "/points/id/$ID");
    }

    public static function serviceExists($ID) {
        if (self::$Redis_Database_Connection === null) {
            self::initDB();
        }

        return self::$Redis_Database_Connection->exists(Config::get("phoenix_api_endpoint") . "/services/id/$ID");
    }

    public static function getService($ID, $Force = false) {
        if (self::$Redis_Database_Connection === null) {
            self::initDB();
        }

        if (self::$Redis_Database_Connection->exists(Config::get("phoenix_api_endpoint") . "/services/id/$ID") && !$Force) {

            $response = json_decode(self::$Redis_Database_Connection->get(Config::get("phoenix_api_endpoint") . "/services/id/$ID"));


            $response->nice_service = Helper::filterAlphaNum($response->name);
            $response->has_image = (file_exists(__DIR__ . "/../../../../" . \crisp\api\Config::get("theme_dir") . "/" . \crisp\api\Config::get("theme") . "/img/logo/" . $response->nice_service . ".svg") ? true : file_exists(__DIR__ . "/../../../../" . \crisp\api\Config::get("theme_dir") . "/" . \crisp\api\Config::get("theme") . "/img/logo/" . $response->nice_service . ".png") );
            $response->image = "/" . \crisp\api\Config::get("theme_dir") . "/" . \crisp\api\Config::get("theme") . "/img/logo/" . $response->nice_service . (file_exists(__DIR__ . "/../../../../" . \crisp\api\Config::get("theme_dir") . "/" . \crisp\api\Config::get("theme") . "/img/logo/" . $response->nice_service . ".svg") ? ".svg" : ".png");
            return $response;
        }

        $curl = \curl_init();
        \curl_setopt_array($curl, array(
            CURLOPT_URL => Config::get("phoenix_url") . Config::get("phoenix_api_endpoint") . "/services/$ID",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_USERAGENT => "CrispCMS ToS;DR",
        ));
        $raw = \curl_exec($curl);
        $response = json_decode($raw);

        if ($response === null) {
            throw new \Exception("Failed to crawl! " . $raw);
        }

        if ($response->error) {
            throw new \Exception($response->error);
        }


        if (self::$Redis_Database_Connection->set(Config::get("phoenix_api_endpoint") . "/services/id/$ID", json_encode($response), 3600) && self::$Redis_Database_Connection->set(Config::get("phoenix_api_endpoint") . "/services/name/" . strtolower($response->name), json_encode($response), 15778476)) {
            $response = json_decode(self::$Redis_Database_Connection->get(Config::get("phoenix_api_endpoint") . "/services/id/$ID"));


            $response->nice_service = Helper::filterAlphaNum($response->name);
            $response->has_image = (file_exists(__DIR__ . "/../../../../" . \crisp\api\Config::get("theme_dir") . "/" . \crisp\api\Config::get("theme") . "/img/logo/" . $response->nice_service . ".svg") ? true : file_exists(__DIR__ . "/../../../../" . \crisp\api\Config::get("theme_dir") . "/" . \crisp\api\Config::get("theme") . "/img/logo/" . $response->nice_service . ".png") );
            $response->image = "/" . \crisp\api\Config::get("theme_dir") . "/" . \crisp\api\Config::get("theme") . "/img/logo/" . $response->nice_service . (file_exists(__DIR__ . "/../../../../" . \crisp\api\Config::get("theme_dir") . "/" . \crisp\api\Config::get("theme") . "/img/logo/" . $response->nice_service . ".svg") ? ".svg" : ".png");
            return $response;
        }
        throw new \Exception("Failed to contact REDIS");
    }

    public static function getTopics($Force = false) {
        if (self::$Redis_Database_Connection === null) {
            self::initDB();
        }

        if (self::$Redis_Database_Connection->exists(Config::get("phoenix_api_endpoint") . "/topics") && !$Force) {
            return json_decode(self::$Redis_Database_Connection->get(Config::get("phoenix_api_endpoint") . "/topics"));
        }

        $curl = \curl_init();
        \curl_setopt_array($curl, array(
            CURLOPT_URL => Config::get("phoenix_url") . Config::get("phoenix_api_endpoint") . "/topics",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_USERAGENT => "CrispCMS ToS;DR",
        ));
        $raw = \curl_exec($curl);
        $response = json_decode($raw);

        if ($response === null) {
            throw new \Exception("Failed to crawl! " . $raw);
        }
        if ($response->error) {
            throw new \Exception($response->error);
        }


        if (self::$Redis_Database_Connection->set(Config::get("phoenix_api_endpoint") . "/topics", json_encode($response), 86400)) {
            return $response;
        }
        throw new \Exception("Failed to contact REDIS");
    }

    public static function getCases($Force = false) {
        if (self::$Redis_Database_Connection === null) {
            self::initDB();
        }

        if (self::$Redis_Database_Connection->exists(Config::get("phoenix_api_endpoint") . "/cases") && !$Force) {
            return json_decode(self::$Redis_Database_Connection->get(Config::get("phoenix_api_endpoint") . "/cases"));
        }

        $curl = \curl_init();
        \curl_setopt_array($curl, array(
            CURLOPT_URL => Config::get("phoenix_url") . Config::get("phoenix_api_endpoint") . "/cases",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_USERAGENT => "CrispCMS ToS;DR",
        ));
        $raw = \curl_exec($curl);
        $response = json_decode($raw);

        if ($response === null) {
            throw new \Exception("Failed to crawl! " . $raw);
        }

        if ($response->error) {
            throw new \Exception($response->error);
        }


        if (self::$Redis_Database_Connection->set(Config::get("phoenix_api_endpoint") . "/cases", json_encode($response), 3600)) {
            return $response;
        }
        throw new \Exception("Failed to contact REDIS");
    }

    public static function getServices($Force = false) {
        if (self::$Redis_Database_Connection === null) {
            self::initDB();
        }

        if (self::$Redis_Database_Connection->exists(Config::get("phoenix_api_endpoint") . "/services") && !$Force) {
            return json_decode(self::$Redis_Database_Connection->get(Config::get("phoenix_api_endpoint") . "/services"));
        }

        $curl = \curl_init();
        \curl_setopt_array($curl, array(
            CURLOPT_URL => Config::get("phoenix_url") . Config::get("phoenix_api_endpoint") . "/services",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_USERAGENT => "CrispCMS ToS;DR",
        ));
        $raw = \curl_exec($curl);
        $response = json_decode($raw);

        if ($response === null) {
            throw new \Exception("Failed to crawl! " . $raw);
        }

        if ($response->error) {
            throw new \Exception($response->error);
        }


        if (self::$Redis_Database_Connection->set(Config::get("phoenix_api_endpoint") . "/services", json_encode($response), 3600)) {
            return $response;
        }
        throw new \Exception("Failed to contact REDIS");
    }

}
