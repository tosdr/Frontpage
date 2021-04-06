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
 * Interact with Phoenix
 */
class Phoenix {

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

    public static function getDBConnector() {
        if (self::$Database_Connection === null) {
            self::initDB();
        }
        return self::$Database_Connection;
    }

    /**
     * Fetches all users from the database.
     */
    public static function fetchAll() {
        if (self::$Database_Connection === null) {
            self::initDB();
        }
        $statement = self::$Database_Connection->prepare("SELECT * FROM users");
        $statement->execute();

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function fetchByID($ID) {
        if (self::$Database_Connection === null) {
            self::initDB();
        }
        $statement = self::$Database_Connection->prepare("SELECT * FROM users WHERE id = :ID");
        $statement->execute(array(":ID" => $ID));

        if ($statement->rowCount() === 0) {
            return false;
        }

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function fetchByEmail($Email) {
        if (self::$Database_Connection === null) {
            self::initDB();
        }
        $statement = self::$Database_Connection->prepare("SELECT * FROM users WHERE email = :Email");
        $statement->execute(array(":Email" => $Email));

        if ($statement->rowCount() === 0) {
            return false;
        }

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function fetchByUsername($Username) {
        if (self::$Database_Connection === null) {
            self::initDB();
        }
        $statement = self::$Database_Connection->prepare("SELECT * FROM users WHERE username = :Username");
        $statement->execute(array(":Username" => $Username));

        if ($statement->rowCount() === 0) {
            return false;
        }

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

}
