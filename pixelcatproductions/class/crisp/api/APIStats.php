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

use \PDO;
use \PDOException;
use \PDORow;
use \PDOStatement;

/**
 * Statistics for the API
 */
class APIStats {

    private static ?PDO $Database_Connection = null;

    public function __construct() {
        self::initDB();
    }

    public static function initDB() {
        $DB = new \crisp\core\MySQL();
        self::$Database_Connection = $DB->getDBConnector();
    }

    /**
     * Add a new statistic or increase it
     * @param string $Interface
     * @param string $Query
     * @return boolean
     */
    public static function add(string $Interface, string $Query) {
        if (self::$Database_Connection === null) {
            self::initDB();
        }

        if (!self::exists($Interface, $Query)) {

            $statement = self::$Database_Connection->prepare("INSERT INTO APIStats (`interface`, `query`, `count`) VALUES (:Interface, :Query, 1)");
            $statement->execute(array(":Interface" => $Interface, ":Query" => $Query));
            if ($statement->rowCount() > 0) {
                return true;
            }
            return false;
        }
        $CurrentCount = self::get($Interface, $Query)["count"];

        $statement = self::$Database_Connection->prepare("UPDATE APIStats SET `count` = :count WHERE interface = :Interface AND query = :Query");
        $statement->execute(array(":Interface" => $Interface, ":Query" => $Query, ":count" => $CurrentCount + 1));
        if ($statement->rowCount() > 0) {
            return true;
        }
        return false;
    }

    /**
     * Get an entry
     * @param string $Interface
     * @param string $Query
     * @return boolean
     */
    public static function get(string $Interface, string $Query) {
        if (self::$Database_Connection === null) {
            self::initDB();
        }
        $statement = self::$Database_Connection->prepare("SELECT * FROM APIStats WHERE interface = :Interface AND query = :Query");
        $statement->execute(array(":Interface" => $Interface, ":Query" => $Query));
        if ($statement->rowCount() > 0) {
            return $statement->fetch(\PDO::FETCH_ASSOC);
        }
        return array();
    }

    /**
     * List all stats
     * @return array
     */
    public static function listAll() {
        if (self::$Database_Connection === null) {
            self::initDB();
        }
        $statement = self::$Database_Connection->prepare("SELECT * FROM APIStats");
        $statement->execute();
        if ($statement->rowCount() > 0) {
            return $statement->fetchAll(\PDO::FETCH_ASSOC);
        }
        return array();
    }
    
    /**
     * List all stats by count
     * @return array
     */
    public static function listAllByCount() {
        if (self::$Database_Connection === null) {
            self::initDB();
        }
        $statement = self::$Database_Connection->prepare("SELECT * FROM APIStats ORDER BY `count` DESC");
        $statement->execute();
        if ($statement->rowCount() > 0) {
            return $statement->fetchAll(\PDO::FETCH_ASSOC);
        }
        return array();
    }

    /**
     * Check if an entry exists
     * @param string $Interface
     * @param string $Query
     * @return boolean
     */
    public static function exists(string $Interface, string $Query) {
        if (self::$Database_Connection === null) {
            self::initDB();
        }
        $statement = self::$Database_Connection->prepare("SELECT * FROM APIStats WHERE interface = :Interface AND query = :Query");
        $statement->execute(array(":Interface" => $Interface, ":Query" => $Query));
        if ($statement->rowCount() > 0) {
            return true;
        }
        return false;
    }

}
