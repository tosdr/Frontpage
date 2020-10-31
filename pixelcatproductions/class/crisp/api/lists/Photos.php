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
 * Interact with all photos stored on the server
 */
class Photos {

    private static ?PDO $Database_Connection = null;

    public function __construct() {
        self::initDB();
    }

    private static function initDB() {
        $DB = new \crisp\core\MySQL();
        self::$Database_Connection = $DB->getDBConnector();
    }

    /**
     * See \crisp\api\User
     * @param type $UserID
     * @return \crisp\api\User
     */
    public function fetchAuthor($PhotoID) {
        if ($PhotoID === null) {
            return null;
        }

        $statement = self::$Database_Connection->prepare("SELECT * FROM Photos WHERE ID = :ID AND Enabled = 1");
        $statement->execute(array(":ID" => $PhotoID));

        $User = new \crisp\api\User($statement->fetch(\PDO::FETCH_ASSOC)["User"]);

        return $User;
    }

    /**
     * Fetches all photos from the database
     * @param bool $FetchIntoClass Should the results be fetched into an array of new \crisp\api\Photo
     * @return array
     */
    public static function fetchAll(bool $FetchIntoClass = true) {
        if (self::$Database_Connection === null) {
            self::initDB();
        }
        $statement = self::$Database_Connection->prepare("SELECT * FROM Photos");
        $statement->execute(array(":Type" => $Type));

        if ($FetchIntoClass) {
            $Array = array();

            foreach ($statement->fetchAll(\PDO::FETCH_ASSOC) as $Photo) {
                array_push($Array, new \crisp\api\Photo($Photo["ID"]));
            }
            return $Array;
        }
        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Fetches all photos randomly from the database
     * 
     * @param bool $FetchIntoClass Should the results be fetched into an array of new \crisp\api\Photo
     * @param int $Limit The limit we should fetch
     * @return array
     */
    public static function fetchAllRandom(bool $FetchIntoClass = true, int $Limit = 100) {
        if (self::$Database_Connection === null) {
            self::initDB();
        }
        $statement = self::$Database_Connection->prepare("SELECT * FROM Photos WHERE Enabled = 1 AND Visible = 1 ORDER BY RAND() LIMIT $Limit");
        $statement->execute();

        if ($FetchIntoClass) {
            $Array = array();

            foreach ($statement->fetchAll(\PDO::FETCH_ASSOC) as $Photo) {
                array_push($Array, new \crisp\api\Photo($Photo["ID"]));
            }
            return $Array;
        }
        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Fetches a photo by its ID
     * @param int $PhotoID The ID of the Photo
     * @param bool $FetchIntoClass Should we fetch the photo into new \crisp\api\Photo
     * @return \crisp\api\Photo
     */
    public static function fetchPhoto(int $PhotoID, bool $FetchIntoClass = true, $Type = 0) {
        if (self::$Database_Connection === null) {
            self::initDB();
        }
        $statement = self::$Database_Connection->prepare("SELECT * FROM Photos WHERE ID = :Photo AND Type = :Type;");
        if ($Type == null) {
            $statement = self::$Database_Connection->prepare("SELECT * FROM Photos WHERE ID = :Photo;");
            $statement->execute(array(":Photo" => $PhotoID));
        } else {
            $statement->execute(array(":Photo" => $PhotoID, ":Type" => $Type));
        }

        if ($FetchIntoClass) {
            return new \crisp\api\Photo($statement->fetch(\PDO::FETCH_ASSOC)["ID"]);
        }
        return $statement->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Fetches all photos of a user
     * @param int $UserID The ID of the user
     * @param bool $FetchIntoClass Should we fetch the photos into an array of new \crisp\api\Photo
     * @return array
     */
    public static function fetchAllByUser(int $UserID, bool $FetchIntoClass = true, $Type = 0) {
        if (self::$Database_Connection === null) {
            self::initDB();
        }
        $statement = self::$Database_Connection->prepare("SELECT * FROM Photos WHERE User = :User AND Enabled = 1 AND Visible = 1 AND Type = :Type;");
        $statement->execute(array(":User" => $UserID, ":Type" => $Type));

        if ($FetchIntoClass) {
            $Array = array();

            foreach ($statement->fetchAll(\PDO::FETCH_ASSOC) as $Photo) {
                array_push($Array, new \crisp\api\Photo($Photo["ID"]));
            }
            return $Array;
        }
        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function fetchAllDisabled(bool $FetchIntoClass = true, $Type = 0) {
        if (self::$Database_Connection === null) {
            self::initDB();
        }
        $statement = self::$Database_Connection->prepare("SELECT * FROM Photos WHERE Enabled = 0 AND Type = :Type;");
        if ($Type == null) {
            $statement = self::$Database_Connection->prepare("SELECT * FROM Photos WHERE Enabled = 0;");
        }
        $statement->execute(array(":Type" => $Type));

        if ($FetchIntoClass) {
            $Array = array();

            foreach ($statement->fetchAll(\PDO::FETCH_ASSOC) as $Photo) {
                array_push($Array, new \crisp\api\Photo($Photo["ID"]));
            }
            return $Array;
        }
        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    public static function fetchPrice($Photo, $Size, $Type) {

        $statement = self::$Database_Connection->prepare("SELECT * FROM Prices WHERE Photo = :ID AND Size = :Size AND Type = :Type");
        $statement->execute(array(":ID" => $Photo, ":Size" => $Size, ":Type" => $Type));

        $result = $statement->fetch(\PDO::FETCH_ASSOC);

        return $result;
    }

    /**
     * Fetches all public photo details of a user, e.g. Thumbnails and non sensitive information
     * @param int $UserID The ID of the user
     * @param bool $FetchIntoClass Should we fetch all photos into an array of new \crisp\api\Photo
     * @return array
     */
    public static function fetchPublicByUser(int $UserID, bool $FetchIntoClass = true, $Type = 0) {
        if (self::$Database_Connection === null) {
            self::initDB();
        }
        $statement = self::$Database_Connection->prepare("SELECT ID,Title,Description,UploadedAt,Latitude,Longitude,Type,ThumbnailMaps,Marker FROM Photos WHERE User = :User AND Enabled = 1 AND Visible = 1 AND Type = :Type;");
        $statement->execute(array(":User" => $UserID, ":Type" => $Type));

        if ($FetchIntoClass) {
            $Array = array();

            foreach ($statement->fetchAll(\PDO::FETCH_ASSOC) as $Photo) {
                array_push($Array, new \crisp\api\Photo($Photo["ID"]));
            }
            return $Array;
        }
        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Fetches all photos filtering sensitive information
     * @param bool $FetchIntoClass Should we fetch the array into new \crisp\api\Photo
     * @return array
     */
    public static function fetchAllPublic(bool $FetchIntoClass = true, $Type = 0) {
        if (self::$Database_Connection === null) {
            self::initDB();
        }
        $statement = self::$Database_Connection->prepare("SELECT ID,Title,Description,UploadedAt,Latitude,Longitude,Type,ThumbnailMaps,Marker FROM Photos WHERE Enabled = 1 AND Visible = 1 AND Type = :Type;");
        if ($Type == null) {
            $statement = self::$Database_Connection->prepare("SELECT ID,Title,Description,UploadedAt,Latitude,Longitude,Type,ThumbnailMaps,Marker FROM Photos WHERE Enabled = 1 AND Visible = 1;");
        }

        $statement->execute(array(":Type" => $Type));

        if ($FetchIntoClass) {
            $Array = array();

            foreach ($statement->fetchAll(\PDO::FETCH_ASSOC) as $Photo) {
                array_push($Array, new \crisp\api\Photo($Photo["ID"]));
            }
            return $Array;
        }
        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * 
     * @param string $PXDM
     * @param string $Title
     * @param string $Description
     * @param string $File
     * @param string $Latitude
     * @param string $Longitude
     * @param int $User
     * @param string $Marker
     * @param type $Type
     * @return boolean|\crisp\api\Photo
     */
    public static function create(string $PXDM, string $Title, string $Description, string $File, string $Latitude, string $Longitude, int $User, string $Marker, $Type = 0) {
        if (self::$Database_Connection === null) {
            self::initDB();
        }
        $statement = self::$Database_Connection->prepare("INSERT INTO Photos (PXDM, Title, Description, Source, Latitude, Longitude, User, Marker, SourcePrint, Type) VALUES (:PXDM, :Title, :Description, :File, :Latitude, :Longitude, :User, :Marker, :File, :Type)");
        $created = $statement->execute(array(":PXDM" => $PXDM, ":Title" => $Title, ":Description" => $Description, ":File" => $File, ":Latitude" => $Latitude, ":Longitude" => $Longitude, ":User" => $User, ":Marker" => $Marker, ":Type" => $Type));
        if ($created) {
            return new \crisp\api\Photo(self::$Database_Connection->lastInsertId());
        }
        return false;
    }

}
