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

namespace crisp\api;

use \PDO;
use \PDOException;
use \PDORow;
use \PDOStatement;

/**
 * Access the translations of the CMS
 */
class Translation {

  /**
   * The Language code
   * @var string|null
   */
  public static ?string $Language = null;
  private static ?PDO $Database_Connection = null;

  /**
   * Sets the language code and inits the database connection for further use of functions in this class
   * @param string|null $Language The Language code or null
   */
  public function __construct($Language = null) {
    self::$Language = $Language;
  }

  /**
   * Inits DB
   */
  private static function initDB() {
    $DB = new \crisp\core\MySQL();
    self::$Database_Connection = $DB->getDBConnector();
  }

  /**
   * Same as \crisp\api\lists\Languages()->fetchLanguages()
   * @uses  \crisp\api\lists\Languages()
   * @param type $FetchIntoClass Should the result be fetched into a \crisp\api\Language class
   * @return \crisp\api\Language|array depending on the $FetchIntoClass parameter
   */
  public static function listLanguages($FetchIntoClass = true) {
    $Languages = new \crisp\api\lists\Languages();
    return $Languages->fetchLanguages($FetchIntoClass);
  }

  /**
   * Retrieves all translations with key and language code
   * @return array containing all translations on the server
   */
  public static function listTranslations() {
    if (self::$Database_Connection === null) {
      self::initDB();
    }
    $statement = self::$Database_Connection->prepare("SELECT * FROM Translations");
    $statement->execute();
    if ($statement->rowCount() > 0) {
      return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }
  }

  /**
   * Retrieves all translations for the specified self::$Language
   * @uses self::$Language
   * @return array containing all translations for the self::$Language
   */
  public static function fetchAll() {
    if (self::$Database_Connection === null) {
      self::initDB();
    }
    $statement = self::$Database_Connection->prepare("SELECT * FROM Translations");
    $statement->execute();
    if ($statement->rowCount() > 0) {

      $Translations = $statement->fetchAll(\PDO::FETCH_ASSOC);

      $Array = array();

      foreach (lists\Languages::fetchLanguages() as $Language) {
        $Array[$Language->getCode()] = array();
        foreach ($Translations as $Item) {
          $Array[$Language->getCode()][$Item["key"]] = $Item[$Language->getCode()];
        }
      }

      return $Array;
    }
    return array();
  }

  /**
   * Fetch all translations by key
   * @param string $Key The letter code
   * @return array
   */
  public static function fetchAllByKey($Key) {
    if (self::$Database_Connection === null) {
      self::initDB();
    }
    $statement = self::$Database_Connection->prepare("SELECT * FROM Translations");
    $statement->execute();
    if ($statement->rowCount() > 0) {

      $Translations = $statement->fetchAll(\PDO::FETCH_ASSOC);

      $Array = array();
      foreach ($Translations as $Item) {
        if (strpos($Item["key"], "plugin.") !== false) {
          continue;
        }
        if ($Item[$Key] === null) {
          continue;
        }
        $Array[$Key][$Item["key"]] = $Item[$Key];
      }

      return $Array[$Key];
    }
    return array();
  }

  /**
   * Check if a translation exists by key
   * @param string $Key The translation key
   * @return bool
   */
  public static function exists($Key) {
    if (self::$Database_Connection === null) {
      self::initDB();
    }
    $statement = self::$Database_Connection->prepare("SELECT * FROM Translations WHERE `key` = :key");
    $statement->execute(array(":key" => $Key));
    return ($statement->rowCount() > 0);
  }

  /**
   * Fetches translations for the specified key
   * @param string $Key The translation key
   * @param int $Count Used for the plural and singular retrieval of translations, also exposes {{ count }} in templates.
   * @param array $UserOptions Custom array used for templating
   * @return string The translation or the key if it doesn't exist
   */
  public static function fetch($Key, $Count = 1, $UserOptions = array()) {

    if (!isset(self::$Language)) {
      self::$Language = Helper::getLocale();
    }


    if (isset($GLOBALS["route"]->GET["debug"]) && $GLOBALS["route"]->GET["debug"] = "translations") {
      return "$Key:" . self::$Language;
    }

    $UserOptions["{{ count }}"] = $Count;


    return nl2br(ngettext(self::get($Key, $UserOptions), self::getPlural($Key, $UserOptions), $Count));
  }

  /**
   * Fetches all singular translations for the specified key
   * @param string $Key The translation key
   * @param array $UserOptions Custom array used for templating
   * @see fetch
   * @see getPlural
   * @return string The translation or the key if it doesn't exist
   */
  public static function get($Key, $UserOptions = array()) {

    if (self::$Database_Connection === null) {
      self::initDB();
    }

    $DBConfig = new Config(self::$Database_Connection);

    foreach ($DBConfig->list(true) as $Item) {
      $GlobalOptions["{{ config.{$Item['key']} }}"] = $Item["value"];
    }

    $Options = array_merge($UserOptions, $GlobalOptions);




    $statement = self::$Database_Connection->prepare("SELECT * FROM Translations WHERE `key` = :Key");
    $statement->execute(array(
        ":Key" => $Key,
            //":Language" => $this->Language
    ));
    if ($statement->rowCount() > 0) {

      $Translation = $statement->fetch(\PDO::FETCH_ASSOC);

      if (!isset($Translation[self::$Language])) {
        if (self::$Language == "en") {
          return $Key;
        }
        return $Translation["en"];
      }

      return strtr($Translation[self::$Language], $Options);
    }
    return $Key;
  }

  /**
   * Fetches all plural translations for the specified key
   * @param string $Key The translation key
   * @param array $UserOptions Custom array used for templating
   * @see fetch
   * @see get
   * @return string The translation or the key if it doesn't exist
   */
  public static function getPlural($Key, $UserOptions = array()) {

    if (self::$Database_Connection === null) {
      self::initDB();
    }

    $DBConfig = new Config(self::$Database_Connection);

    foreach ($DBConfig->list(true) as $Item) {
      $GlobalOptions["{{ config.{$Item['key']} }}"] = $Item["value"];
    }

    $Options = array_merge($UserOptions, $GlobalOptions);



    $statement = self::$Database_Connection->prepare("SELECT * FROM Translations WHERE `key` = :Key");
    $statement->execute(array(
        ":Key" => $Key . ".plural",
            //":Language" => $this->Language
    ));
    if ($statement->rowCount() > 0) {
      $Translation = $statement->fetch(\PDO::FETCH_ASSOC);

      if ($Translation[self::$Language] === null) {
        if (self::$Language == "en") {
          return $Key . ".plural";
        }
        return strtr($Translation["en"], $Options);
      }

      return strtr($Translation[self::$Language], $Options);
    }
    return $Key . ".plural";
  }

}
