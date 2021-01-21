<?php

/*
 * Copyright 2020 Pixelcat Productions <support@pixelcatproductions.net>
 * @author 2020 Justin René Back <jback@pixelcatproductions.net>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace crisp\api;

use \PDO;
use \PDOException;
use \PDORow;
use \PDOStatement;

/**
 * The error reporting system
 */
class ErrorReporter {

  private static ?PDO $Database_Connection = null;

  public function __construct() {
    self::initDB();
  }

  public static function initDB() {
    $DB = new \crisp\core\MySQL();
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
  public static function create(int $HttpStatusCode, string $Traceback, string $Summary, string $Prefix = "ise_") {
    if (self::$Database_Connection === null) {
      self::initDB();
    }
    $ReferenceID = \crisp\core\Crypto::UUIDv4($Prefix);
    if (php_sapi_name() !== 'cli') {
      header("X-Error-ReferenceID: $ReferenceID");
    }
    $statement = self::$Database_Connection->prepare("INSERT INTO Crashes (`ReferenceID`, `HttpStatusCode`, `Traceback`, `Summary`) VALUES (:ReferenceID, :HttpStatusCode, :Traceback, :Summary)");
    $statement->execute(array(":ReferenceID" => $ReferenceID, ":HttpStatusCode" => $HttpStatusCode, ":Traceback" => $Traceback, ":Summary" => $Summary));
    if ($statement->rowCount() > 0) {
      return $ReferenceID;
    }
    return false;
  }

}
