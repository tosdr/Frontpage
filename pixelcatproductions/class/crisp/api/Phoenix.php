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
 * Some useful phoenix functions
 */
class Phoenix {

    private static ?\Redis $Redis_Database_Connection = null;
    private static ?\PDO $Postgres_Database_Connection = null;


    private static function initPGDB() {
        $PostgresDB = new \crisp\core\Postgres();
        self::$Postgres_Database_Connection = $PostgresDB->getDBConnector();
    }

    private static function initDB() {
        $RedisDB = new \crisp\core\Redis();
        self::$Redis_Database_Connection = $RedisDB->getDBConnector();
    }

    public static function getPointsByServicePG(string $ID) {
        if (self::$Redis_Database_Connection === NULL) {
            self::initDB();
        }

        if (self::$Redis_Database_Connection->keys("pg_pointsbyservice_$ID")) {
            return unserialize(self::$Redis_Database_Connection->get("pg_pointsbyservice_$ID"));
        }

        if (self::$Postgres_Database_Connection === NULL) {
            self::initPGDB();
        }



        $statement = self::$Postgres_Database_Connection->prepare("SELECT * FROM points WHERE service_id = :ID");

        $statement->execute(array(":ID" => $ID));

        $Result = $statement->fetchAll(\PDO::FETCH_ASSOC);

        self::$Redis_Database_Connection->set("pg_pointsbyservice_$ID", serialize($Result), 300);

        return $Result;
    }

    public static function getPointPG(string $ID) {
        if (self::$Redis_Database_Connection === NULL) {
            self::initDB();
        }

        if (self::$Redis_Database_Connection->keys("pg_point_$ID")) {
            return unserialize(self::$Redis_Database_Connection->get("pg_point_$ID"));
        }

        if (self::$Postgres_Database_Connection === NULL) {
            self::initPGDB();
        }

        $statement = self::$Postgres_Database_Connection->prepare("SELECT * FROM points WHERE id = :ID");

        $statement->execute(array(":ID" => $ID));

        $Result = $statement->fetch(\PDO::FETCH_ASSOC);

        self::$Redis_Database_Connection->set("pg_point_$ID", serialize($Result), 300);

        return $Result;
    }

    /**
     * Get details of a point from phoenix
     * @param string $ID The ID of the point
     * @param bool $Force Force update from phoenix
     * @return object
     * @deprecated Use Phoenix::getPointPG
     * @throws \Exception
     */
    public static function getPoint(string $ID, bool $Force = false) {
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

    public static function getCasePG(string $ID) {
        if (self::$Redis_Database_Connection === NULL) {
            self::initDB();
        }

        if (self::$Redis_Database_Connection->keys("pg_case_$ID")) {
            return unserialize(self::$Redis_Database_Connection->get("pg_case_$ID"));
        }


        if (self::$Postgres_Database_Connection === NULL) {
            self::initPGDB();
        }

        $statement = self::$Postgres_Database_Connection->prepare("SELECT * FROM cases WHERE id = :ID");

        $statement->execute(array(":ID" => $ID));

        $Result = $statement->fetch(\PDO::FETCH_ASSOC);

        self::$Redis_Database_Connection->set("pg_case_$ID", serialize($Result), 300);

        return $Result;
    }

    /**
     * Get details of a case
     * @param string $ID The ID of a case
     * @param bool $Force Force update from Phoenix
     * @return object
     * @deprecated Use Phoenix::getCasePG
     * @throws \Exception
     */
    public static function getCase(string $ID, bool $Force = false) {
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

    public static function getTopicPG(string $ID) {
        if (self::$Postgres_Database_Connection === NULL) {
            self::initDB();
        }

        if (self::$Redis_Database_Connection->keys("pg_topic_$ID")) {
            return unserialize(self::$Redis_Database_Connection->get("pg_topic_$ID"));
        }

        if (self::$Postgres_Database_Connection === NULL) {
            self::initPGDB();
        }

        $statement = self::$Postgres_Database_Connection->prepare("SELECT * FROM topics WHERE id = :ID");

        $statement->execute(array(":ID" => $ID));

        $Result = $statement->fetch(\PDO::FETCH_ASSOC);

        self::$Redis_Database_Connection->set("pg_topic_$ID", serialize($Result), 300);

        return $Result;
    }

    /**
     * Get details of a topic
     * @param string $ID The topic id
     * @param bool $Force Force update from phoenix
     * @return object
     * @deprecated Use Phoenix::getTopicPG
     * @throws \Exception
     */
    public static function getTopic(string $ID, bool $Force = false) {
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

    /**
     * Get details of a service by name
     * @param string $Name The name of the service
     * @param bool $Force Force update from phoenix
     * @return object
     * @deprecated Use Phoenix::getServiceByNamePG
     * @throws \Exception
     */
    public static function getServiceByName(string $Name, bool$Force = false) {
        $Name = strtolower($Name);
        if (self::$Redis_Database_Connection === null) {
            self::initDB();
        }

        if (self::$Redis_Database_Connection->exists(Config::get("phoenix_api_endpoint") . "/services/name/$Name") && !$Force) {


            $response = json_decode(self::$Redis_Database_Connection->get(Config::get("phoenix_api_endpoint") . "/services/name/$Name"));

            $response->nice_service = Helper::filterAlphaNum($response->name);
            $response->has_image = (file_exists(__DIR__ . "/../../../../" . \crisp\api\Config::get("theme_dir") . "/" . \crisp\api\Config::get("theme") . "/img/logo/" . $response->nice_service . ".svg") ? true : file_exists(__DIR__ . "/../../../../" . \crisp\api\Config::get("theme_dir") . "/" . \crisp\api\Config::get("theme") . "/img/logo/" . $response->nice_service . ".png") );
            $response->image = "/" . \crisp\api\Config::get("theme_dir") . "/" . \crisp\api\Config::get("theme") . "/img/logo/" . $response->nice_service . (file_exists(__DIR__ . "/../../../../" . \crisp\api\Config::get("theme_dir") . "/" . \crisp\api\Config::get("theme") . "/img/logo/" . $response->nice_service . ".svg") ? ".svg" : ".png");

            return $response;
        }
        throw new \Exception("Service is not initialized!");
    }
    
    

    public static function searchServiceByNamePG(string $Name) {
        if (self::$Postgres_Database_Connection === NULL) {
            self::initDB();
        }

        if (self::$Redis_Database_Connection->keys("pg_searchservicebyname_$Name")) {
            return unserialize(self::$Redis_Database_Connection->get("pg_searchservicebyname_$Name"));
        }

        if (self::$Postgres_Database_Connection === NULL) {
            self::initPGDB();
        }

        $statement = self::$Postgres_Database_Connection->prepare("SELECT * FROM services WHERE LOWER(name) LIKE :ID");

        $statement->execute(array(":ID" => "%$Name%"));

        $Result = $statement->fetchAll(\PDO::FETCH_ASSOC);

        self::$Redis_Database_Connection->set("pg_searchservicebyname_$Name", serialize($Result), 300);

        return $Result;
    }

    public static function getServiceByNamePG(string $Name) {
        $Name = strtolower($Name);
        if (self::$Postgres_Database_Connection === NULL) {
            self::initDB();
        }

        if (self::$Redis_Database_Connection->keys("pg_getservicebyname_$Name")) {
            return unserialize(self::$Redis_Database_Connection->get("pg_getservicebyname_$Name"));
        }

        if (self::$Postgres_Database_Connection === NULL) {
            self::initPGDB();
        }

        $statement = self::$Postgres_Database_Connection->prepare("SELECT * FROM services WHERE LOWER(name) = :ID");

        $statement->execute(array(":ID" => $Name));

        $Result = $statement->fetch(\PDO::FETCH_ASSOC);

        self::$Redis_Database_Connection->set("pg_getservicebyname_$Name", serialize($Result), 300);

        return $Result;
    }

    public static function serviceExistsByNamePG(string $Name) {
        if (self::$Postgres_Database_Connection === NULL) {
            self::initDB();
        }

        if (self::$Redis_Database_Connection->keys("pg_serviceexistsbyname_$Name")) {
            return unserialize(self::$Redis_Database_Connection->get("pg_serviceexistsbyname_$Name"));
        }

        if (self::$Postgres_Database_Connection === NULL) {
            self::initPGDB();
        }

        $statement = self::$Postgres_Database_Connection->prepare("SELECT * FROM services WHERE name = :ID");

        $statement->execute(array(":ID" => $Name));

        $Result = ($statement->rowCount() > 0 ? true : false);

        self::$Redis_Database_Connection->set("pg_serviceexistsbyname_$Name", serialize($Result), 300);

        return $Result;
    }

    /**
     * Check if a service exists by name
     * @param string $Name The name of the service
     * @return bool
     * @deprecated Use Phoenix::serviceExistsByNamePG
     */
    public static function serviceExistsByName(string $Name) {
        $Name = strtolower($Name);

        if (self::$Redis_Database_Connection === null) {
            self::initDB();
        }

        return self::$Redis_Database_Connection->exists(Config::get("phoenix_api_endpoint") . "/services/name/$Name");
    }

    /**
     * Check if the point exists by name
     * @param string $ID The ID of the point
     * @deprecated Use Phoenix::pointExistsPG
     * @return bool
     */
    public static function pointExists(string $ID) {
        if (self::$Redis_Database_Connection === null) {
            self::initDB();
        }

        return self::$Redis_Database_Connection->exists(Config::get("phoenix_api_endpoint") . "/points/id/$ID");
    }

    public static function pointExistsPG(string $ID) {
        if (self::$Postgres_Database_Connection === NULL) {
            self::initDB();
        }

        if (self::$Redis_Database_Connection->keys("pg_pointexists_$ID")) {
            return unserialize(self::$Redis_Database_Connection->get("pg_pointexists_$ID"));
        }

        if (self::$Postgres_Database_Connection === NULL) {
            self::initPGDB();
        }

        $statement = self::$Postgres_Database_Connection->prepare("SELECT * FROM points WHERE id = :ID");

        $statement->execute(array(":ID" => $ID));

        $Result = ($statement->rowCount() > 0 ? true : false);

        self::$Redis_Database_Connection->set("pg_pointexists_$ID", serialize($Result), 300);

        return $Result;
    }

    public static function serviceExistsPG(string $ID) {
        if (self::$Postgres_Database_Connection === NULL) {
            self::initDB();
        }

        if (self::$Redis_Database_Connection->keys("pg_serviceexists_$ID")) {
            return unserialize(self::$Redis_Database_Connection->get("pg_serviceexists_$ID"));
        }

        if (self::$Postgres_Database_Connection === NULL) {
            self::initPGDB();
        }

        $statement = self::$Postgres_Database_Connection->prepare("SELECT * FROM services WHERE id = :ID");

        $statement->execute(array(":ID" => $ID));

        $Result = ($statement->rowCount() > 0 ? true : false);

        self::$Redis_Database_Connection->set("pg_serviceexists_$ID", serialize($Result), 300);

        return $Result;
    }

    /**
     * Check if a service exists by name
     * @param string $ID The ID of the service
     * @return bool
     * @deprecated Use Phoenix::serviceExistsPG
     */
    public static function serviceExists(string $ID) {
        if (self::$Redis_Database_Connection === null) {
            self::initDB();
        }

        return self::$Redis_Database_Connection->exists(Config::get("phoenix_api_endpoint") . "/services/id/$ID");
    }

    public static function getServicePG(string $ID) {
        if (self::$Postgres_Database_Connection === NULL) {
            self::initDB();
        }

        if (self::$Redis_Database_Connection->keys("pg_service_$ID")) {
            $response = unserialize(self::$Redis_Database_Connection->get("pg_service_$ID"));
            $response["nice_service"] = Helper::filterAlphaNum($response["name"]);
            $response["has_image"] = (file_exists(__DIR__ . "/../../../../" . \crisp\api\Config::get("theme_dir") . "/" . \crisp\api\Config::get("theme") . "/img/logo/" . $response["nice_service"] . ".svg") ? true : file_exists(__DIR__ . "/../../../../" . \crisp\api\Config::get("theme_dir") . "/" . \crisp\api\Config::get("theme") . "/img/logo/" . $response["nice_service"] . ".png") );
            $response["image"] = "/" . \crisp\api\Config::get("theme_dir") . "/" . \crisp\api\Config::get("theme") . "/img/logo/" . $response["nice_service"] . (file_exists(__DIR__ . "/../../../../" . \crisp\api\Config::get("theme_dir") . "/" . \crisp\api\Config::get("theme") . "/img/logo/" . $response["nice_service"] . ".svg") ? ".svg" : ".png");
            return $response;
        }

        if (self::$Postgres_Database_Connection === NULL) {
            self::initPGDB();
        }

        $statement = self::$Postgres_Database_Connection->prepare("SELECT * FROM services WHERE id = :ID");

        $statement->execute(array(":ID" => $ID));

        $response = $statement->fetch(\PDO::FETCH_ASSOC);

        self::$Redis_Database_Connection->set("pg_service_$ID", serialize($response), 300);


        $response["nice_service"] = Helper::filterAlphaNum($response["name"]);
        $response["has_image"] = (file_exists(__DIR__ . "/../../../../" . \crisp\api\Config::get("theme_dir") . "/" . \crisp\api\Config::get("theme") . "/img/logo/" . $response["nice_service"] . ".svg") ? true : file_exists(__DIR__ . "/../../../../" . \crisp\api\Config::get("theme_dir") . "/" . \crisp\api\Config::get("theme") . "/img/logo/" . $response["nice_service"] . ".png") );
        $response["image"] = "/" . \crisp\api\Config::get("theme_dir") . "/" . \crisp\api\Config::get("theme") . "/img/logo/" . $response["nice_service"] . (file_exists(__DIR__ . "/../../../../" . \crisp\api\Config::get("theme_dir") . "/" . \crisp\api\Config::get("theme") . "/img/logo/" . $response["nice_service"] . ".svg") ? ".svg" : ".png");
        return $response;
    }

    /**
     * Get details of a service by name
     * @param string $ID The ID of a service
     * @param bool $Force Force update from phoenix
     * @return object
     * @deprecated Use Phoenix::getServicePG
     * @throws \Exception
     */
    public static function getService(string $ID, bool $Force = false) {
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


        if (self::$Redis_Database_Connection->set(Config::get("phoenix_api_endpoint") . "/services/id/$ID", json_encode($response), 43200) && self::$Redis_Database_Connection->set(Config::get("phoenix_api_endpoint") . "/services/name/" . strtolower($response->name), json_encode($response), 15778476)) {
            $response = json_decode(self::$Redis_Database_Connection->get(Config::get("phoenix_api_endpoint") . "/services/id/$ID"));


            $response->nice_service = Helper::filterAlphaNum($response->name);
            $response->has_image = (file_exists(__DIR__ . "/../../../../" . \crisp\api\Config::get("theme_dir") . "/" . \crisp\api\Config::get("theme") . "/img/logo/" . $response->nice_service . ".svg") ? true : file_exists(__DIR__ . "/../../../../" . \crisp\api\Config::get("theme_dir") . "/" . \crisp\api\Config::get("theme") . "/img/logo/" . $response->nice_service . ".png") );
            $response->image = "/" . \crisp\api\Config::get("theme_dir") . "/" . \crisp\api\Config::get("theme") . "/img/logo/" . $response->nice_service . (file_exists(__DIR__ . "/../../../../" . \crisp\api\Config::get("theme_dir") . "/" . \crisp\api\Config::get("theme") . "/img/logo/" . $response->nice_service . ".svg") ? ".svg" : ".png");
            return $response;
        }
        throw new \Exception("Failed to contact REDIS");
    }

    public static function getTopicsPG() {
        if (self::$Postgres_Database_Connection === NULL) {
            self::initDB();
        }

        if (self::$Redis_Database_Connection->keys("pg_topics")) {
            return unserialize(self::$Redis_Database_Connection->get("pg_topics"));
        }

        if (self::$Postgres_Database_Connection === NULL) {
            self::initPGDB();
        }

        $Result = self::$Postgres_Database_Connection->query("SELECT * FROM topics")->fetchAll(\PDO::FETCH_ASSOC);

        self::$Redis_Database_Connection->set("pg_topics", serialize($Result), 300);

        return $Result;
    }

    /**
     * Get a list of topics
     * @param bool $Force Force update from phoenix
     * @return object
     * @deprecated Use Phoenix::getServicesPG
     * @throws \Exception
     */
    public static function getTopics(bool $Force = false) {
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

    public static function getCasesPG() {
        if (self::$Redis_Database_Connection === NULL) {
            self::initDB();
        }

        if (self::$Redis_Database_Connection->keys("pg_cases")) {
            return unserialize(self::$Redis_Database_Connection->get("pg_cases"));
        }

        if (self::$Postgres_Database_Connection === NULL) {
            self::initPGDB();
        }

        $Result = self::$Postgres_Database_Connection->query("SELECT * FROM cases")->fetchAll(\PDO::FETCH_ASSOC);

        self::$Redis_Database_Connection->set("pg_cases", serialize($Result), 300);

        return $Result;
    }

    /**
     * Get a list of cases
     * @param bool $Force Force update from phoenix
     * @return object
     * @deprecated Use Phoenix::getCasesPG
     * @throws \Exception
     */
    public static function getCases(bool $Force = false) {
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

    public static function getServicesPG() {
        if (self::$Postgres_Database_Connection === NULL) {
            self::initDB();
        }

        if (self::$Redis_Database_Connection->keys("pg_services")) {
            return unserialize(self::$Redis_Database_Connection->get("pg_services"));
        }

        if (self::$Postgres_Database_Connection === NULL) {
            self::initPGDB();
        }

        $Result = self::$Postgres_Database_Connection->query("SELECT * FROM services")->fetchAll(\PDO::FETCH_ASSOC);

        self::$Redis_Database_Connection->set("pg_services", serialize($Result), 300);

        return $Result;
    }

    /**
     * Get a list of services
     * @param bool $Force Force update from phoenix
     * @return object
     * @deprecated Please use Phoenix::getServicesPG
     * @throws \Exception
     */
    public static function getServices(bool $Force = false) {
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
