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
