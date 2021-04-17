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


namespace crisp\plugin\curator;

use \PDO;
use \PDOException;
use \PDORow;
use \PDOStatement;

/**
 * Interact with all registered users on the site
 */
class Users {

    private static ?PDO $Database_Connection = null;

    public function __construct() {
        self::initDB();
    }

    /**
     * Initiate the database, else Database_Connection is null :-)
     */
    private static function initDB() {
        $DB = new \crisp\core\Postgres();
        self::$Database_Connection = $DB->getDBConnector();
    }

    /**
     * Fetches all users from the database.
     * @return array|\crisp\plugin\curator\User Array of User objects
     */
    public static function fetchAll() {
        if (self::$Database_Connection === null) {
            self::initDB();
        }
        $statement = self::$Database_Connection->prepare("SELECT * FROM users");
        $statement->execute();

        $Array = array();

        foreach ($statement->fetchAll(\PDO::FETCH_ASSOC) as $User) {
            array_push($Array, new \crisp\plugin\curator\PhoenixUser($User["id"]));
        }
        return $Array;
    }

    /**
     * Fetches users by id from the database.
     * @param string $ID
     * @return \crisp\plugin\curator\User
     */
    public static function fetchByID($Email) {
        if (self::$Database_Connection === null) {
            self::initDB();
        }
        $statement = self::$Database_Connection->prepare("SELECT * FROM users WHERE id = :Email");
        $statement->execute(array(":Email" => $Email));

        if ($statement->rowCount() === 0) {
            return false;
        }

        $User = $statement->fetch(\PDO::FETCH_ASSOC);

        return new \crisp\plugin\curator\PhoenixUser($User["id"]);
    }

    /**
     * Fetches users by email from the database.
     * @param string $Email
     * @return \crisp\plugin\curator\User
     */
    public static function fetchByEmail($Email) {
        if (self::$Database_Connection === null) {
            self::initDB();
        }
        $statement = self::$Database_Connection->prepare("SELECT * FROM users WHERE email = :Email");
        $statement->execute(array(":Email" => $Email));

        if ($statement->rowCount() === 0) {
            return false;
        }

        $User = $statement->fetch(\PDO::FETCH_ASSOC);

        return new \crisp\plugin\curator\PhoenixUser($User["id"]);
    }
}
