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

use crisp\core\Crypto;
use crisp\core\MySQL;
use PDO;

/**
 * The error reporting system
 */
class ErrorReporter {

  private static ?PDO $Database_Connection = null;

  public function __construct() {
    self::initDB();
  }

  public static function initDB() {
    $DB = new MySQL();
    self::$Database_Connection = $DB->getDBConnector();
  }

  /**
   * Create a new Crash report
   * @param int $HttpStatusCode HTTP code
   * @param string $Traceback A traceback e.g Exceptions
   * @param string $Summary A summary of what happened
   * @param string $Prefix of the reference id
   * @return boolean|string Returns ReferenceID if successful otherwise false
   */
  public static function create(int $HttpStatusCode, string $Traceback, string $Summary, string $Prefix = "ise_"): bool|string
  {
    if (self::$Database_Connection === null) {
      self::initDB();
    }
    $ReferenceID = Crypto::UUIDv4($Prefix);
    if (php_sapi_name() !== 'cli') {
      header("X-Error-ReferenceID: $ReferenceID");
    }
    $statement = self::$Database_Connection->prepare("INSERT INTO Crashes (ReferenceID, HttpStatusCode, Traceback, Summary) VALUES (:ReferenceID, :HttpStatusCode, :Traceback, :Summary)");
    $statement->execute(array(":ReferenceID" => $ReferenceID, ":HttpStatusCode" => $HttpStatusCode, ":Traceback" => $Traceback, ":Summary" => $Summary));
    if ($statement->rowCount() > 0) {
      return $ReferenceID;
    }
    return false;
  }

}
