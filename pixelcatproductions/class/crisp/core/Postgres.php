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


namespace crisp\core;

/**
 * Interact with the database yourself. Please use this interface only when you REALLY need it for custom tables.
 * We offer a variety of functions to interact with users or the system itself in a safe way :-)
 */
class Postgres {

  private $Database_Connection;

  /**
   * Constructs the Database_Connection
   * @see getDBConnector
   */
  public function __construct() {
    if (isset($_GET["simulate_heroku_kill"])) {
      throw new \Exception("Failed to contact edit.tosdr.org");
    }

    $EnvFile = parse_ini_file(__DIR__ . "/../../../../.env");

    $db = "";
    if (isset($EnvFile["POSTGRES_URI"]) && !empty($EnvFile["POSTGRES_URI"])) {
      $db = parse_url($EnvFile["POSTGRES_URI"]);
    } else {
      $db = parse_url(\crisp\api\Config::get("plugin_heroku_database_uri"));
    }

    try {
      $pdo = new \PDO("pgsql:" . sprintf(
                      "host=%s;port=%s;user=%s;password=%s;dbname=%s",
                      $db["host"],
                      $db["port"],
                      $db["user"],
                      $db["pass"],
                      ltrim($db["path"], "/")
      ));
      $this->Database_Connection = $pdo;
    } catch (\Exception $ex) {
      throw new \Exception($ex);
    }
  }

  /**
   * Get the database connector
   * @return \PDO
   */
  public function getDBConnector() {
    return $this->Database_Connection;
  }

}
