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


namespace crisp\api\lists;

use crisp\api\Language;
use crisp\core\MySQL;
use PDO;

/**
 * Interact with all languages stored on the server
 */
class Languages {

  private static ?PDO $Database_Connection = null;

  public function __construct() {
    self::initDB();
  }

  private static function initDB() {
    $DB = new MySQL();
    self::$Database_Connection = $DB->getDBConnector();
  }

    /**
     * Fetches all languages
     * @param bool $FetchIntoClass Should we fetch the result into new \crisp\api\Language()?
     * @return array|Language with all languages
     */
  public static function fetchLanguages(bool $FetchIntoClass = true): array|Language
  {
    if (self::$Database_Connection === null) {
      self::initDB();
    }
    $statement = self::$Database_Connection->prepare("SELECT * FROM Languages");
    $statement->execute();

    if ($FetchIntoClass) {
      $Array = array();

      foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $Language) {
        array_push($Array, new Language($Language["id"]));
      }
      return $Array;
    }
    return $statement->fetchAll(PDO::FETCH_ASSOC);
  }

  /**
   * Create a new language
   * @param string $Name The name of the language
   * @param string $Code The letter code of the language e.g. en-US, de, es, ru
   * @param string $NativeName How is the language called in the native tongue?
   * @param string $Flag Path to the flag image on the server
   * @param bool $Enabled Enable/disable the language
   * @return bool TRUE if action was successful
   */
  public static function createLanguage(string $Name, string $Code, string $NativeName, string $Flag, bool $Enabled = true): bool
  {
    if (self::$Database_Connection === null) {
      self::initDB();
    }
    self::$Database_Connection->beginTransaction();
    $statement = self::$Database_Connection->prepare("INSERT INTO Languages (Name, Code, NativeName, Flag, Enabled) VALUES (:Name, :Code, :NativeName, :Flag, :Enabled)");
    $success = $statement->execute(array(":Name" => $Name, ":Code" => $Code, ":NativeName" => $NativeName, ":Flag" => $Flag, ":Enabled" => $Enabled));


    if (!$success) {
      return !self::$Database_Connection->rollBack();
    }


    $statement2 = self::$Database_Connection->prepare("SELECT table_name, column_name, data_type FROM information_schema.columns WHERE table_name = 'translations' AND column_name = '$Code';");
    $statement2->execute();
    if ($statement2->rowCount() > 0) {
      return self::$Database_Connection->commit();
    }


    $statement3 = self::$Database_Connection->prepare("ALTER TABLE Translations ADD COLUMN $Code TEXT NULL");

    $success3 = $statement3->execute();


    if ($success3) {
      return self::$Database_Connection->commit();
    }
    return !self::$Database_Connection->rollBack();
  }

    /**
     * Check if a language exists by country code
     * @param string|int $Code The language's country code
     * @return bool
     */
    public static function languageExists(string|int $Code): bool
  {
    if (self::$Database_Connection === null) {
      self::initDB();
    }
    $statement = self::$Database_Connection->prepare("SELECT * FROM Languages WHERE Code = :code");
    $statement->execute(array(":code" => $Code));
    return $statement->rowCount() > 0;
  }

    /**
     * Fetches a language by country code
     * @param string $Code The language's country code
     * @param bool $FetchIntoClass Should we fetch the result into new \crisp\api\Language()?
     * @return bool|Language|array with the language
     */
  public static function getLanguageByCode(string $Code, bool $FetchIntoClass = true): bool|array|Language
  {
    if (self::$Database_Connection === null) {
      self::initDB();
    }
    $statement = self::$Database_Connection->prepare("SELECT * FROM Languages WHERE Code = :code");
    $statement->execute(array(":code" => $Code));
    if ($statement->rowCount() > 0) {
      if ($FetchIntoClass) {
        return new Language($statement->fetch(PDO::FETCH_ASSOC)["id"]);
      }
      return $statement->fetch(PDO::FETCH_ASSOC);
    }

    $Flag = strtolower($Code);

    if (str_contains($Flag, "_")) {
      $Flag = substr($Flag, 3);
    }


    if (Languages::createLanguage("base.language.$Code", $Code, "base.language.native.$Code", $Flag)) {
      return self::getLanguageByCode($Code, $FetchIntoClass);
    }

    return false;
  }

  /**
   * Fetches a language by ID
   * @param int|string $ID The database ID of the language
   * @param bool Should we fetch the result into new \crisp\api\Language()?
   * @return bool|Language|array with the language
   */
  public static function getLanguageByID(int|string $ID, bool $FetchIntoClass = true): bool|array|Language
  {
    if (self::$Database_Connection === null) {
      self::initDB();
    }
    $statement = self::$Database_Connection->prepare("SELECT * FROM Languages WHERE ID = :ID");
    $statement->execute(array(":ID" => $ID));
    if ($statement->rowCount() > 0) {
      if ($FetchIntoClass) {
        return new Language($statement->fetch(PDO::FETCH_ASSOC)["id"]);
      }
      return $statement->fetch(PDO::FETCH_ASSOC);
    }
    return false;
  }

}
