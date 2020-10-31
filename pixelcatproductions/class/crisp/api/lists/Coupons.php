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

namespace crisp\api\lists;

use \PDO;
use \PDOException;
use \PDORow;
use \PDOStatement;

/**
 * Interact with all categories stored on the server
 */
class Coupons {

    private static ?PDO $Database_Connection = null;

    public function __construct() {
        self::initDB();
    }

    private static function initDB() {
        $DB = new \crisp\core\MySQL();
        self::$Database_Connection = $DB->getDBConnector();
    }

    /**
     * Fetches all categories from the database
     * @return array
     */
    public static function fetchAll() {
        if (self::$Database_Connection === null) {
            self::initDB();
        }
        $statement = self::$Database_Connection->prepare("SELECT * FROM Coupons");
        $statement->execute();

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function exists($ID) {
        if (self::$Database_Connection === null) {
            self::initDB();
        }
        $statement = self::$Database_Connection->prepare("SELECT * FROM Coupons WHERE Code = :ID");
        $statement->execute(array(":ID" => $ID));

        return ($statement->rowCount() === 0 ? false : true);
    }

    public static function fetchByID($ID, bool $FetchIntoClass = true) {
        if (self::$Database_Connection === null) {
            self::initDB();
        }
        $statement = self::$Database_Connection->prepare("SELECT * FROM Coupons WHERE ID = :ID");
        $statement->execute(array(":ID" => $ID));

        if ($statement->rowCount() === 0) {
            return false;
        }

        if (!$FetchIntoClass) {
            return $statement->fetch(\PDO::FETCH_ASSOC);
        }
        return new \crisp\api\Coupon($statement->fetch(\PDO::FETCH_ASSOC)["ID"]);
    }

    public static function fetchByCode($ID, bool $FetchIntoClass = true) {
        if (self::$Database_Connection === null) {
            self::initDB();
        }
        $statement = self::$Database_Connection->prepare("SELECT * FROM Coupons WHERE Code = :ID");
        $statement->execute(array(":ID" => $ID));

        if ($statement->rowCount() === 0) {
            return false;
        }

        if (!$FetchIntoClass) {
            return $statement->fetch(\PDO::FETCH_ASSOC);
        }
        return new \crisp\api\Coupon($statement->fetch(\PDO::FETCH_ASSOC)["ID"]);
    }

}
