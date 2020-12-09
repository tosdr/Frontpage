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

namespace crisp\plugin\admin;

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
        $DB = new \crisp\core\MySQL();
        self::$Database_Connection = $DB->getDBConnector();
    }

    /**
     * Fetches all users from the database.
     * @return array|\crisp\plugin\admin\User Array of User objects
     */
    public static function fetchAll() {
        if (self::$Database_Connection === null) {
            self::initDB();
        }
        $statement = self::$Database_Connection->prepare("SELECT * FROM Users");
        $statement->execute();

        $Array = array();

        foreach ($statement->fetchAll(\PDO::FETCH_ASSOC) as $User) {
            array_push($Array, new \crisp\plugin\admin\User($User["ID"]));
        }
        return $Array;
    }

    /**
     * Fetches users by id from the database.
     * @param string $ID
     * @return \crisp\plugin\admin\User
     */
    public static function fetchByID($Email) {
        if (self::$Database_Connection === null) {
            self::initDB();
        }
        $statement = self::$Database_Connection->prepare("SELECT * FROM Users WHERE ID = :Email");
        $statement->execute(array(":Email" => $Email));

        if ($statement->rowCount() === 0) {
            return false;
        }

        $User = $statement->fetch(\PDO::FETCH_ASSOC);

        return new \crisp\plugin\admin\User($User["ID"]);
    }

    /**
     * Fetches users by email from the database.
     * @param string $Email
     * @return \crisp\plugin\admin\User
     */
    public static function fetchByEmail($Email) {
        if (self::$Database_Connection === null) {
            self::initDB();
        }
        $statement = self::$Database_Connection->prepare("SELECT * FROM Users WHERE Email = :Email");
        $statement->execute(array(":Email" => $Email));

        if ($statement->rowCount() === 0) {
            return false;
        }

        $User = $statement->fetch(\PDO::FETCH_ASSOC);

        return new \crisp\plugin\admin\User($User["ID"]);
    }

    /**
     * Creates a new user. This dummy user has no information saved, please use the \crisp\plugin\admin\User functions
     * @return \crisp\plugin\admin\User The Object of the User.
     */
    public static function create() {
        if (self::$Database_Connection === null) {
            self::initDB();
        }
        $statement = self::$Database_Connection->prepare("INSERT INTO Users () VALUES ()");
        $statement->execute();
        if ($statement->rowCount() > 0) {
            return new \crisp\plugin\admin\User(self::$Database_Connection->lastInsertId());
        }
        throw new Exception("Failed to create user");
    }

}
