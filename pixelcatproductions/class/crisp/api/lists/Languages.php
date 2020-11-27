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
 * Interact with all languages stored on the server
 */
class Languages {

    private static ?PDO $Database_Connection = null;

    public function __construct() {
        self::initDB();
    }

    private static function initDB() {
        $DB = new \crisp\core\MySQL();
        self::$Database_Connection = $DB->getDBConnector();
    }

    /**
     * Fetches all languages
     * @param boolean $FetchIntoClass Should we fetch the result into new \crisp\api\Language()?
     * @return boolean|array|\crisp\api\Language with all languages
     */
    public static function fetchLanguages($FetchIntoClass = true) {
        if (self::$Database_Connection === null) {
            self::initDB();
        }
        $statement = self::$Database_Connection->prepare("SELECT * FROM Languages");
        $statement->execute();

        if ($FetchIntoClass) {
            $Array = array();

            foreach ($statement->fetchAll(\PDO::FETCH_ASSOC) as $Language) {
                array_push($Array, new \crisp\api\Language($Language["ID"]));
            }
            return $Array;
        }
        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function createLanguage($Name, $Code, $NativeName, $Flag, $Enabled = true) {
        if (self::$Database_Connection === null) {
            self::initDB();
        }
        self::$Database_Connection->beginTransaction();
        $statement = self::$Database_Connection->prepare("INSERT INTO Languages (Name, Code, NativeName, Flag, Enabled) VALUES (:Name, :Code, :NativeName, :Flag, :Enabled)");
        $success = $statement->execute(array(":Name" => $Name, ":Code" => $Code, ":NativeName" => $NativeName, ":Flag" => $Flag, ":Enabled" => $Enabled));


        if (!$success) {
            return !self::$Database_Connection->rollBack();
        }


        $statement2 = self::$Database_Connection->prepare("SHOW COLUMNS FROM `Translations` LIKE '$Code'");
        $statement2->execute();
        if ($statement2->rowCount() > 0) {
            return self::$Database_Connection->commit();
        }


        $statement3 = self::$Database_Connection->prepare("ALTER TABLE Translations ADD COLUMN `$Code` TEXT NULL");

        $success3 = $statement3->execute();


        if ($success3) {
            return self::$Database_Connection->commit();
        }
        return !self::$Database_Connection->rollBack();
    }

    /**
     * Fetches a language by country code
     * @param type $Code The language's country code
     * @param type $FetchIntoClass Should we fetch the result into new \crisp\api\Language()?
     * @return boolean|\crisp\api\Language|array with the language
     */
    public static function getLanguageByCode($Code, $FetchIntoClass = true) {
        if (self::$Database_Connection === null) {
            self::initDB();
        }
        $statement = self::$Database_Connection->prepare("SELECT * FROM Languages WHERE Code = :code");
        $statement->execute(array(":code" => $Code));
        if ($statement->rowCount() > 0) {
            if ($FetchIntoClass) {
                return new \crisp\api\Language($statement->fetch(\PDO::FETCH_ASSOC)["ID"]);
            }
            return $statement->fetch(\PDO::FETCH_ASSOC);
        }

        if (\crisp\api\lists\Languages::createLanguage($Code, $Code, $Code, $Code)) {
            return self::getLanguageByCode($Code, $FetchIntoClass);
        }

        return false;
    }

    /**
     * Fetches a language by ID
     * @param type $ID The database ID of the language
     * @param type Should we fetch the result into new \crisp\api\Language()?
     * @return boolean|\crisp\api\Language|array with the language
     */
    public static function getLanguageByID($ID, $FetchIntoClass = true) {
        if (self::$Database_Connection === null) {
            self::initDB();
        }
        $statement = self::$Database_Connection->prepare("SELECT * FROM Languages WHERE ID = :ID");
        $statement->execute(array(":ID" => $ID));
        if ($statement->rowCount() > 0) {
            if ($FetchIntoClass) {
                return new \crisp\api\Language($statement->fetch(\PDO::FETCH_ASSOC)["ID"]);
            }
            return $statement->fetch(\PDO::FETCH_ASSOC);
        }
        return false;
    }

}
