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

use crisp\core\MySQL;
use PDO;

/**
 * Interact with an api key
 */
class APIKey {

    public string $APIKey;
    private ?PDO $Database_Connection;

    public function __construct($APIKey) {
        $DB = new MySQL();
        $this->Database_Connection = $DB->getDBConnector();
        $this->APIKey = $APIKey;
    }

    /**
     * Fetches a Keys details
     * @return array|null
     */
    public function fetch(): ?array
    {
        if ($this->APIKey === null) {
            return null;
        }

        $statement = $this->Database_Connection->prepare("SELECT * FROM APIKeys WHERE `key` = :ID");
        $statement->execute(array(":ID" => $this->APIKey));

        return $statement->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Enables an api key
     * @return bool|null
     * @see disable
     */
    public function enable(): ?bool
    {
        if ($this->APIKey === null) {
            return null;
        }

        $statement = $this->Database_Connection->prepare("UPDATE APIKeys SET revoked = 0 WHERE `key` = :ID");
        return $statement->execute(array(":ID" => $this->APIKey));
    }

    /**
     * Disables an api key
     * @return bool|null
     * @see enable
     */
    public function disable(): ?bool
    {
        if ($this->APIKey === null) {
            return null;
        }

        $statement = $this->Database_Connection->prepare("UPDATE APIKeys SET revoked = 0 WHERE `key` = :ID");
        return $statement->execute(array(":ID" => $this->APIKey));
    }

    /**
     * Checks whether a language is enabled or not
     * @return bool|null
     */
    public function isEnabled(): ?bool
    {
        if ($this->APIKey === null) {
            return null;
        }

        $statement = $this->Database_Connection->prepare("SELECT * FROM APIKeys WHERE `key` = :ID");
        $statement->execute(array(":ID" => $this->APIKey));

        return !$statement->fetch(PDO::FETCH_ASSOC)["revoked"];
    }

    /**
     * Check if the language exists in the database
     * @return bool|null
     */
    public function exists(): ?bool
    {
        if ($this->APIKey === null) {
            return null;
        }

        $statement = $this->Database_Connection->prepare("SELECT * FROM APIKeys WHERE `key` = :ID");
        $statement->execute(array(":ID" => $this->APIKey));

        return ($statement->rowCount() != 0);
    }

}
