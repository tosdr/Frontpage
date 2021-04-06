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
