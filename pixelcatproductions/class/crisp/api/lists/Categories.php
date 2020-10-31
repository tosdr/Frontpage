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
class Categories {

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
        $statement = self::$Database_Connection->prepare("SELECT * FROM Categories");
        $statement->execute();

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function exists($ID) {
        if (self::$Database_Connection === null) {
            self::initDB();
        }
        $statement = self::$Database_Connection->prepare("SELECT * FROM Categories WHERE ID = :ID");
        $statement->execute(array(":ID" => $ID));

        return ($statement->rowCount() === 0 ? false : true);
    }

    public static function fetchEnabled() {
        if (self::$Database_Connection === null) {
            self::initDB();
        }
        $statement = self::$Database_Connection->prepare("SELECT * FROM Categories WHERE Enabled = 1");
        $statement->execute();

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function fetchAllPhotos($ID, $FetchPhoto = false) {
        if (self::$Database_Connection === null) {
            self::initDB();
        }

        $Array = array();

        $statement = self::$Database_Connection->prepare("SELECT * FROM AssignedCategories WHERE Category = :ID");
        $statement->execute(array(":ID" => $ID));

        foreach ($statement->fetchAll(\PDO::FETCH_ASSOC) as $Photo) {
            if ($FetchPhoto) {
                $Array[] = new \crisp\api\Photo($Photo["Photo"]);
            } else {

                $Photo = new \crisp\api\Photo($Photo["Photo"]);

                $Array[] = $Photo->fetch();
            }
        }

        return $Array;
    }

    /**
     * Fetches all categories randomly from the database
     * 
     * @param int $Limit The limit we should fetch
     * @return array
     */
    public static function fetchAllRandom(int $Limit = 100) {
        if (self::$Database_Connection === null) {
            self::initDB();
        }
        $statement = self::$Database_Connection->prepare("SELECT * FROM Categories ORDER BY RAND() LIMIT $Limit");
        $statement->execute();

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

}
