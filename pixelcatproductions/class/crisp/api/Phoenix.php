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

  /**
   * Generates tosdr.org api data from a service id
   * @param string $ID The service ID from Phoenix to generate the API Files from
   * @return array The API data
   */
  public static function generateApiFiles(string $ID, int $Version = 1) {
    if (self::$Redis_Database_Connection === NULL) {
      self::initDB();
    }

    if (self::$Redis_Database_Connection->keys("pg_generateapifiles_" . $ID . "_$Version")) {
      return unserialize(self::$Redis_Database_Connection->get("pg_generateapifiles_" . $ID . "_$Version"));
    }

    if (self::$Postgres_Database_Connection === NULL) {
      self::initPGDB();
    }
    $SkeletonData = null;

    switch ($Version) {
      case 1:
      case 2:
        $ServiceLinks = array();
        $ServicePoints = array();
        $ServicePointsData = array();

        $points = self::getPointsByServicePG($ID);
        $service = self::getServicePG($ID);
        $documents = self::getDocumentsByServicePG($ID);
        foreach ($documents as $Links) {
          $ServiceLinks[$Links["name"]] = array(
              "name" => $Links["name"],
              "url" => $Links["url"]
          );
        }
        foreach ($points as $Point) {
          if ($Point["status"] == "approved") {
            array_push($ServicePoints, $Point["id"]);
          }
        }
        foreach ($points as $Point) {
          $Document = array_column($documents, null, 'id')[$Point["document_id"]];
          $Case = self::getCasePG($Point["case_id"]);
          if ($Point["status"] == "approved") {
            $ServicePointsData[$Point["id"]] = array(
                "discussion" => "https://edit.tosdr.org/points/" . $Point["id"],
                "id" => $Point["id"],
                "needsModeration" => ($Point["status"] != "approved"),
                "quoteDoc" => $Document["name"],
                "quoteText" => $Point["quoteText"],
                "services" => array($ID),
                "set" => "set+service+and+topic",
                "slug" => $Point["slug"],
                "title" => $Point["title"],
                "topics" => array(),
                "tosdr" => array(
                    "binding" => true,
                    "case" => $Case["title"],
                    "point" => $Case["classification"],
                    "score" => $Case["score"],
                    "tldr" => $Point["analysis"]
                ),
            );
          }
        }

        $SkeletonData = array(
            "id" => $service["id"],
            "name" => $service["name"],
            "slug" => $service["slug"],
            "image" => \crisp\core\Themes::includeResource($service["image"]),
            "class" => ($service["rating"] == "N/A" ? false : ($service["is_comprehensively_reviewed"] ? $service["rating"] : false)),
            "links" => $ServiceLinks,
            "points" => $ServicePoints,
            "pointsData" => $ServicePointsData,
            "urls" => explode(",", $service["url"])
        );
        break;
      case 3:
        $ServiceLinks = array();
        $ServicePoints = array();
        $ServicePointsData = array();

        $points = self::getPointsByServicePG($ID);
        $service = self::getServicePG($ID);
        $documents = self::getDocumentsByServicePG($ID);
        foreach ($points as $Point) {
          $Document = array_column($documents, null, 'id')[$Point["document_id"]];
          $Case = self::getCasePG($Point["case_id"]);
          $ServicePointsData[] = array(
              "discussion" => "https://edit.tosdr.org/points/" . $Point["id"],
              "id" => $Point["id"],
              "needsModeration" => ($Point["status"] != "approved"),
              "document" => $Document,
              "quote" => $Point["quoteText"],
              "services" => array($ID),
              "set" => "set+service+and+topic",
              "slug" => $Point["slug"],
              "title" => $Point["title"],
              "topics" => array(),
              "case" => $Case
          );
        }

        $SkeletonData = $service;

        $SkeletonData["image"] = \crisp\core\Themes::includeResource($service["image"]);
        $SkeletonData["documents"] = $documents;
        $SkeletonData["points"] = $ServicePointsData;
        $SkeletonData["urls"] = explode(",", $service["url"]);
        break;
    }

    self::$Redis_Database_Connection->set("pg_generateapifiles_" . $ID . "_$Version", serialize($SkeletonData), 900);

    return $SkeletonData;
  }

  /**
   * Retrieve points by a service from postgres
   * @see https://github.com/tosdr/edit.tosdr.org/blob/8b900bf8879b8ed3a4a2a6bbabbeafa7d2ab540c/db/schema.rb#L89-L111 Database Schema
   * @param string $ID The ID of the Service
   * @return array
   */
  public static function getPointsByServicePG($ID) {
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



    self::$Redis_Database_Connection->set("pg_pointsbyservice_$ID", serialize($Result), 900);

    return $Result;
  }

  /**
   * Get all documents by a service from postgres
   * @see https://github.com/tosdr/edit.tosdr.org/blob/8b900bf8879b8ed3a4a2a6bbabbeafa7d2ab540c/db/schema.rb#L64-L77 Database Schema
   * @param string $ID The Service ID
   * @return array
   */
  public static function getDocumentsByServicePG(string $ID) {
    if (self::$Redis_Database_Connection === NULL) {
      self::initDB();
    }

    if (self::$Redis_Database_Connection->keys("pg_getdocumentbyservice_$ID")) {
      return unserialize(self::$Redis_Database_Connection->get("pg_getdocumentbyservice_$ID"));
    }

    if (self::$Postgres_Database_Connection === NULL) {
      self::initPGDB();
    }

    $statement = self::$Postgres_Database_Connection->prepare("SELECT * FROM documents WHERE service_id = :ID");

    $statement->execute(array(":ID" => $ID));

    $Result = $statement->fetchAll(\PDO::FETCH_ASSOC);

    self::$Redis_Database_Connection->set("pg_getdocumentbyservice_$ID", serialize($Result), 900);

    return $Result;
  }

  /**
   * List all points from postgres
   * @see https://github.com/tosdr/edit.tosdr.org/blob/8b900bf8879b8ed3a4a2a6bbabbeafa7d2ab540c/db/schema.rb#L89-L111 Database Schema
   * @return array
   */
  public static function getPointsPG() {
    if (self::$Postgres_Database_Connection === NULL) {
      self::initDB();
    }

    if (self::$Redis_Database_Connection->keys("pg_points")) {
      return unserialize(self::$Redis_Database_Connection->get("pg_points"));
    }

    if (self::$Postgres_Database_Connection === NULL) {
      self::initPGDB();
    }

    $Result = self::$Postgres_Database_Connection->query("SELECT * FROM points")->fetchAll(\PDO::FETCH_ASSOC);

    self::$Redis_Database_Connection->set("pg_points", serialize($Result), 900);

    return $Result;
  }

  /**
   * Gets details about a point from postgres
   * @see https://github.com/tosdr/edit.tosdr.org/blob/8b900bf8879b8ed3a4a2a6bbabbeafa7d2ab540c/db/schema.rb#L89-L111 Database Schema
   * @param string $ID The ID of a point
   * @return array
   */
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

    self::$Redis_Database_Connection->set("pg_point_$ID", serialize($Result), 900);

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

  /**
   * Gets details about a case from postgres
   * @see https://github.com/tosdr/edit.tosdr.org/blob/8b900bf8879b8ed3a4a2a6bbabbeafa7d2ab540c/db/schema.rb#L42-L52 Database Schema
   * @param string $ID The id of a case
   * @return array
   */
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

    self::$Redis_Database_Connection->set("pg_case_$ID", serialize($Result), 900);

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

  /**
   * Gets details about a topic from postgres
   * @see https://github.com/tosdr/edit.tosdr.org/blob/8b900bf8879b8ed3a4a2a6bbabbeafa7d2ab540c/db/schema.rb#L170-L177 Database Schema
   * @param string $ID The topic id
   * @return array
   */
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

    self::$Redis_Database_Connection->set("pg_topic_$ID", serialize($Result), 900);

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
      $response->image = "/img/logo/" . $response->nice_service . (file_exists(__DIR__ . "/../../../../" . \crisp\api\Config::get("theme_dir") . "/" . \crisp\api\Config::get("theme") . "/img/logo/" . $response->nice_service . ".svg") ? ".svg" : ".png");

      return $response;
    }
    throw new \Exception("Service is not initialized!");
  }

  /**
   * Search for a service via postgres
   * @see https://github.com/tosdr/edit.tosdr.org/blob/8b900bf8879b8ed3a4a2a6bbabbeafa7d2ab540c/db/schema.rb#L134-L148 Database Schema
   * @param string $Name The name of a service
   * @return array
   */
  public static function searchServiceByNamePG(string $Name) {
    if (self::$Postgres_Database_Connection === NULL) {
      self::initDB();
    }

    if (self::$Redis_Database_Connection->keys("pg_searchservicebyname_$Name")) {
      $response = unserialize(self::$Redis_Database_Connection->get("pg_searchservicebyname_$Name"));

      foreach ($response as $Key => $Service) {
        $response[$Key]["nice_service"] = Helper::filterAlphaNum($response[$Key]["name"]);
        $response[$Key]["has_image"] = (file_exists(__DIR__ . "/../../../../" . \crisp\api\Config::get("theme_dir") . "/" . \crisp\api\Config::get("theme") . "/img/logo/" . $response[$Key]["nice_service"] . ".svg") ? true : file_exists(__DIR__ . "/../../../../" . \crisp\api\Config::get("theme_dir") . "/" . \crisp\api\Config::get("theme") . "/img/logo/" . $response[$Key]["nice_service"] . ".png") );
        $response[$Key]["image"] = "/img/logo/" . $response[$Key]["nice_service"] . (file_exists(__DIR__ . "/../../../../" . \crisp\api\Config::get("theme_dir") . "/" . \crisp\api\Config::get("theme") . "/img/logo/" . $response[$Key]["nice_service"] . ".svg") ? ".svg" : ".png");
      }
      return $response;
    }

    if (self::$Postgres_Database_Connection === NULL) {
      self::initPGDB();
    }

    $statement = self::$Postgres_Database_Connection->prepare("SELECT * FROM services WHERE LOWER(name) LIKE :ID");

    $statement->execute(array(":ID" => "%$Name%"));

    $response = $statement->fetchAll(\PDO::FETCH_ASSOC);

    foreach ($response as $Key => $Service) {
      $response[$Key]["nice_service"] = Helper::filterAlphaNum($response[$Key]["name"]);
      $response[$Key]["has_image"] = (file_exists(__DIR__ . "/../../../../" . \crisp\api\Config::get("theme_dir") . "/" . \crisp\api\Config::get("theme") . "/img/logo/" . $response[$Key]["nice_service"] . ".svg") ? true : file_exists(__DIR__ . "/../../../../" . \crisp\api\Config::get("theme_dir") . "/" . \crisp\api\Config::get("theme") . "/img/logo/" . $response[$Key]["nice_service"] . ".png") );
      $response[$Key]["image"] = "/img/logo/" . $response[$Key]["nice_service"] . (file_exists(__DIR__ . "/../../../../" . \crisp\api\Config::get("theme_dir") . "/" . \crisp\api\Config::get("theme") . "/img/logo/" . $response[$Key]["nice_service"] . ".svg") ? ".svg" : ".png");
    }

    self::$Redis_Database_Connection->set("pg_searchservicebyname_$Name", serialize($response), 900);

    return $response;
  }

  /**
   * Get details of a service from postgres via a slug
   * @see https://github.com/tosdr/edit.tosdr.org/blob/8b900bf8879b8ed3a4a2a6bbabbeafa7d2ab540c/db/schema.rb#L134-L148 Database Schema
   * @param string $Name The slug of a service
   * @return array
   */
  public static function getServiceBySlugPG(string $Name) {
    if (self::$Postgres_Database_Connection === NULL) {
      self::initDB();
    }

    if (self::$Redis_Database_Connection->keys("pg_getservicebyslug_$Name")) {
      return unserialize(self::$Redis_Database_Connection->get("pg_getservicebyslug_$Name"));
    }

    if (self::$Postgres_Database_Connection === NULL) {
      self::initPGDB();
    }

    $statement = self::$Postgres_Database_Connection->prepare("SELECT * FROM services WHERE LOWER(slug) = LOWER(:ID)");

    $statement->execute(array(":ID" => $Name));


    if ($statement->rowCount() == 0) {
      return false;
    }

    $Result = $statement->fetch(\PDO::FETCH_ASSOC);

    self::$Redis_Database_Connection->set("pg_getservicebyslug_$Name", serialize($Result), 900);

    return $Result;
  }

  /**
   * Get details of a service via postgres by name
   * @see https://github.com/tosdr/edit.tosdr.org/blob/8b900bf8879b8ed3a4a2a6bbabbeafa7d2ab540c/db/schema.rb#L134-L148 Database Schema
   * @param string $Name the exact name of the service
   * @return array
   */
  public static function getServiceByNamePG(string $Name) {
    if (self::$Postgres_Database_Connection === NULL) {
      self::initDB();
    }

    if (self::$Redis_Database_Connection->keys("pg_getservicebyname_$Name")) {
      $response = unserialize(self::$Redis_Database_Connection->get("pg_getservicebyname_$Name"));
      $response["nice_service"] = Helper::filterAlphaNum($response["name"]);
      $response["has_image"] = (file_exists(__DIR__ . "/../../../../" . \crisp\api\Config::get("theme_dir") . "/" . \crisp\api\Config::get("theme") . "/img/logo/" . $response["nice_service"] . ".svg") ? true : file_exists(__DIR__ . "/../../../../" . \crisp\api\Config::get("theme_dir") . "/" . \crisp\api\Config::get("theme") . "/img/logo/" . $response["nice_service"] . ".png") );
      $response["image"] = "/img/logo/" . $response["nice_service"] . (file_exists(__DIR__ . "/../../../../" . \crisp\api\Config::get("theme_dir") . "/" . \crisp\api\Config::get("theme") . "/img/logo/" . $response["nice_service"] . ".svg") ? ".svg" : ".png");
      return $response;
    }

    if (self::$Postgres_Database_Connection === NULL) {
      self::initPGDB();
    }

    $statement = self::$Postgres_Database_Connection->prepare("SELECT * FROM services WHERE LOWER(name) = LOWER(:ID)");

    $statement->execute(array(":ID" => $Name));

    if ($statement->rowCount() == 0) {
      return false;
    }

    $response = $statement->fetch(\PDO::FETCH_ASSOC);

    self::$Redis_Database_Connection->set("pg_getservicebyname_$Name", serialize($response), 900);
    $response["nice_service"] = Helper::filterAlphaNum($response["name"]);
    $response["has_image"] = (file_exists(__DIR__ . "/../../../../" . \crisp\api\Config::get("theme_dir") . "/" . \crisp\api\Config::get("theme") . "/img/logo/" . $response["nice_service"] . ".svg") ? true : file_exists(__DIR__ . "/../../../../" . \crisp\api\Config::get("theme_dir") . "/" . \crisp\api\Config::get("theme") . "/img/logo/" . $response["nice_service"] . ".png") );
    $response["image"] = "/img/logo/" . $response["nice_service"] . (file_exists(__DIR__ . "/../../../../" . \crisp\api\Config::get("theme_dir") . "/" . \crisp\api\Config::get("theme") . "/img/logo/" . $response["nice_service"] . ".svg") ? ".svg" : ".png");
    return $response;
  }

  /**
   * Check if a service exists from postgres via slug
   * @param string $Name The slug of the service
   * @return bool
   */
  public static function serviceExistsBySlugPG(string $Name) {
    if (self::$Postgres_Database_Connection === NULL) {
      self::initDB();
    }

    if (self::$Redis_Database_Connection->keys("pg_serviceexistsbyslug_$Name")) {
      return unserialize(self::$Redis_Database_Connection->get("pg_serviceexistsbyslug_$Name"));
    }

    if (self::$Postgres_Database_Connection === NULL) {
      self::initPGDB();
    }

    $statement = self::$Postgres_Database_Connection->prepare("SELECT * FROM services WHERE LOWER(slug) = LOWER(:ID)");

    $statement->execute(array(":ID" => $Name));

    $Result = ($statement->rowCount() > 0 ? true : false);

    self::$Redis_Database_Connection->set("pg_serviceexistsbyslug_$Name", serialize($Result), 900);

    return $Result;
  }

  /**
   * Check if a service exists from postgres via name
   * @param string $Name The name of the service
   * @return bool
   */
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

    $statement = self::$Postgres_Database_Connection->prepare("SELECT * FROM services WHERE LOWER(name) = LOWER(:ID)");

    $statement->execute(array(":ID" => $Name));

    $Result = ($statement->rowCount() > 0 ? true : false);

    self::$Redis_Database_Connection->set("pg_serviceexistsbyname_$Name", serialize($Result), 900);

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

  /**
   * Check if a point exists from postgres via slug
   * @param string $ID The id of the point
   * @return bool
   */
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

    self::$Redis_Database_Connection->set("pg_pointexists_$ID", serialize($Result), 900);

    return $Result;
  }

  /**
   * Check if a service exists from postgres via the ID
   * @param string $ID The ID of the service
   * @return bool
   */
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

    self::$Redis_Database_Connection->set("pg_serviceexists_$ID", serialize($Result), 900);

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
      $response["image"] = "/img/logo/" . $response["nice_service"] . (file_exists(__DIR__ . "/../../../../" . \crisp\api\Config::get("theme_dir") . "/" . \crisp\api\Config::get("theme") . "/img/logo/" . $response["nice_service"] . ".svg") ? ".svg" : ".png");
      return $response;
    }

    if (self::$Postgres_Database_Connection === NULL) {
      self::initPGDB();
    }

    $statement = self::$Postgres_Database_Connection->prepare("SELECT * FROM services WHERE id = :ID");

    $statement->execute(array(":ID" => $ID));

    if ($statement->rowCount() == 0) {
      return false;
    }

    $response = $statement->fetch(\PDO::FETCH_ASSOC);

    self::$Redis_Database_Connection->set("pg_service_$ID", serialize($response), 900);


    $response["nice_service"] = Helper::filterAlphaNum($response["name"]);
    $response["has_image"] = (file_exists(__DIR__ . "/../../../../" . \crisp\api\Config::get("theme_dir") . "/" . \crisp\api\Config::get("theme") . "/img/logo/" . $response["nice_service"] . ".svg") ? true : file_exists(__DIR__ . "/../../../../" . \crisp\api\Config::get("theme_dir") . "/" . \crisp\api\Config::get("theme") . "/img/logo/" . $response["nice_service"] . ".png") );
    $response["image"] = "/img/logo/" . $response["nice_service"] . (file_exists(__DIR__ . "/../../../../" . \crisp\api\Config::get("theme_dir") . "/" . \crisp\api\Config::get("theme") . "/img/logo/" . $response["nice_service"] . ".svg") ? ".svg" : ".png");
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
      $response->image = "/img/logo/" . $response->nice_service . (file_exists(__DIR__ . "/../../../../" . \crisp\api\Config::get("theme_dir") . "/" . \crisp\api\Config::get("theme") . "/img/logo/" . $response->nice_service . ".svg") ? ".svg" : ".png");
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
      $response->image = "/img/logo/" . $response->nice_service . (file_exists(__DIR__ . "/../../../../" . \crisp\api\Config::get("theme_dir") . "/" . \crisp\api\Config::get("theme") . "/img/logo/" . $response->nice_service . ".svg") ? ".svg" : ".png");
      return $response;
    }
    throw new \Exception("Failed to contact REDIS");
  }

  /**
   * List all topics from postgres
   * @see https://github.com/tosdr/edit.tosdr.org/blob/8b900bf8879b8ed3a4a2a6bbabbeafa7d2ab540c/db/schema.rb#L170-L177 Database Schema
   * @return array
   */
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

    self::$Redis_Database_Connection->set("pg_topics", serialize($Result), 900);

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

  /**
   * List all cases from postgres
   * @see https://github.com/tosdr/edit.tosdr.org/blob/8b900bf8879b8ed3a4a2a6bbabbeafa7d2ab540c/db/schema.rb#L42-L52 Database Schema
   * @return array
   */
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

    self::$Redis_Database_Connection->set("pg_cases", serialize($Result), 900);

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

  /**
   * List all services from postgres
   * @see https://github.com/tosdr/edit.tosdr.org/blob/8b900bf8879b8ed3a4a2a6bbabbeafa7d2ab540c/db/schema.rb#L134-L148 Database Schema
   * @return array
   */
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

    $Result = self::$Postgres_Database_Connection->query("SELECT * FROM services WHERE status IS NULL or status = ''")->fetchAll(\PDO::FETCH_ASSOC);

    self::$Redis_Database_Connection->set("pg_services", serialize($Result), 900);

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
