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

use \PDO;
use \PDOException;
use \PDORow;
use \PDOStatement;

/**
 * Interact with an api key
 */
class APIKey {

    private static ?PDO $Database_Connection = null;
    public string $APIKey;

    public function __construct($APIKey) {
        $DB = new \crisp\core\MySQL();
        $this->Database_Connection = $DB->getDBConnector();
        $this->APIKey = $APIKey;
    }

    /**
     * Fetches a Keys details
     * @return array
     */
    public function fetch() {
        if ($this->LanguageID === null) {
            return null;
        }

        $statement = $this->Database_Connection->prepare("SELECT * FROM APIKeys WHERE `key` = :ID");
        $statement->execute(array(":ID" => $this->APIKey));

        return $statement->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Enables an api key
     * @see disable
     * @return boolean
     */
    public function enable() {
        if ($this->LanguageID === null) {
            return null;
        }

        $statement = $this->Database_Connection->prepare("UPDATE APIKeys SET revoked = 0 WHERE `key` = :ID");
        return $statement->execute(array(":ID" => $this->APIKey));
    }

    /**
     * Disables an api key
     * @see enable
     * @return boolean
     */
    public function disable() {
        if ($this->LanguageID === null) {
            return null;
        }

        $statement = $this->Database_Connection->prepare("UPDATE APIKeys SET revoked = 0 WHERE `key` = :ID");
        return $statement->execute(array(":ID" => $this->APIKey));
    }

    /**
     * Checks wether a language is enabled or not
     * @return boolean
     */
    public function isEnabled() {
        if ($this->LanguageID === null) {
            return null;
        }

        $statement = $this->Database_Connection->prepare("SELECT * FROM APIKeys WHERE `key` = :ID");
        $statement->execute(array(":ID" => $this->APIKey));

        return !$statement->fetch(\PDO::FETCH_ASSOC)["revoked"];
    }

    /**
     * Check if the language exists in the database
     * @return boolean
     */
    public function exists() {
        if ($this->LanguageID === null) {
            return null;
        }

        $statement = $this->Database_Connection->prepare("SELECT * FROM APIKeys WHERE `key` = :ID");
        $statement->execute(array(":ID" => $this->APIKey));

        return ($statement->rowCount() != 0);
    }

}
