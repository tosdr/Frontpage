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
 * Interact with a language
 */
class Language extends \crisp\api\lists\Languages {

    private PDO $Database_Connection;
    public int $LanguageID;
    public $Language;

    public function __construct($LanguageID) {
        $DB = new \crisp\core\MySQL();
        $this->Database_Connection = $DB->getDBConnector();
        if (is_numeric($LanguageID)) {
            $this->LanguageID = $LanguageID;
        } else {
            $this->Language = $LanguageID;
        }
    }

    /**
     * Fetches a language's details
     * @return array
     */
    public function fetch() {
        if ($this->LanguageID === null) {
            return null;
        }

        $statement = $this->Database_Connection->prepare("SELECT * FROM Languages WHERE ID = :ID");
        $statement->execute(array(":ID" => $this->LanguageID));

        return $statement->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Enables a language
     * @see disable
     * @return Boolean
     */
    public function enable() {
        if ($this->LanguageID === null) {
            return null;
        }

        $statement = $this->Database_Connection->prepare("UPDATE Languages SET Enabled = 1 WHERE ID = :ID");
        return $statement->execute(array(":ID" => $this->LanguageID));
    }

    /**
     * Disables a language
     * @see enable
     * @return Boolean
     */
    public function disable() {
        if ($this->LanguageID === null) {
            return null;
        }

        $statement = $this->Database_Connection->prepare("UPDATE Languages SET Enabled = 0 WHERE ID = :ID");
        return $statement->execute(array(":ID" => $this->LanguageID));
    }

    /**
     * Checks wether a language is enabled or not
     * @return Boolean
     */
    public function isEnabled() {
        if ($this->LanguageID === null) {
            return null;
        }

        $statement = $this->Database_Connection->prepare("SELECT Enabled FROM Languages WHERE ID = :ID");
        $statement->execute(array(":ID" => $this->LanguageID));

        return $statement->fetch(\PDO::FETCH_ASSOC)["Enabled"];
    }

    /**
     * Sets a new name for the language
     * @param string $Name The new name of the language
     * @return Boolean TRUE if successfully set, otherwise false
     */
    public function setName(string $Name) {
        if ($this->LanguageID === null) {
            return null;
        }

        $statement = $this->Database_Connection->prepare("UPDATE Languages SET Name = :Name WHERE ID = :ID");
        return $statement->execute(array(":Name" => $Name, ":ID" => $this->LanguageID));
    }

    /**
     * Gets the name of the language
     * @return String
     */
    public function getName() {
        if ($this->LanguageID === null) {
            return null;
        }

        $statement = $this->Database_Connection->prepare("SELECT Name FROM Languages WHERE ID = :ID");
        $statement->execute(array(":ID" => $this->LanguageID));

        return $statement->fetch(\PDO::FETCH_ASSOC)["Name"];
    }

    /**
     * Sets the code of the language
     * @param string $Code The new language code
     * @return Boolean TRUE if successfully set, otherwise false
     */
    public function setCode(string $Code) {
        if ($this->LanguageID === null) {
            return null;
        }

        $statement = $this->Database_Connection->prepare("UPDATE Languages SET Code = :Code WHERE ID = :ID");
        return $statement->execute(array(":Code" => $Code, ":ID" => $this->LanguageID));
    }

    /**
     * Gets the code of a language
     * @return Boolean
     */
    public function getCode() {
        if ($this->LanguageID === null) {
            return null;
        }

        $statement = $this->Database_Connection->prepare("SELECT Code FROM Languages WHERE ID = :ID");
        $statement->execute(array(":ID" => $this->LanguageID));

        return $statement->fetch(\PDO::FETCH_ASSOC)["Code"];
    }
    

    /**
     * Sets the new native name of the language
     * @param string $NativeName The new native name
     * @return Boolean TRUE if successfully set, otherwise false
     */
    public function setNativeName(string $NativeName) {
        if ($this->LanguageID === null) {
            return null;
        }

        $statement = $this->Database_Connection->prepare("UPDATE Languages SET NativeName = :NativeName WHERE ID = :ID");
        return $statement->execute(array(":NativeName" => $NativeName, ":ID" => $this->LanguageID));
    }

    /**
     * Gets the native name of a language
     * @return String
     */
    public function getNativeName() {
        if ($this->LanguageID === null) {
            return null;
        }

        $statement = $this->Database_Connection->prepare("SELECT NativeName FROM Languages WHERE ID = :ID");
        $statement->execute(array(":ID" => $this->LanguageID));

        return $statement->fetch(\PDO::FETCH_ASSOC)["NativeName"];
    }

    /**
     * Sets the flag icon of a language
     * @param string $Flag The flag icon name, see Themes
     * @return Boolean TRUE if successfully set, otherwise false
     */
    public function setFlag(string $Flag) {
        if ($this->LanguageID === null) {
            return null;
        }

        $statement = $this->Database_Connection->prepare("UPDATE Languages SET v = :Flag WHERE ID = :ID");
        return $statement->execute(array(":Flag" => $Flag, ":ID" => $this->LanguageID));
    }

    /**
     * Gets the flag icon of a language
     * @return String
     */
    public function getFlag() {
        if ($this->LanguageID === null) {
            return null;
        }

        $statement = $this->Database_Connection->prepare("SELECT Flag FROM Languages WHERE ID = :ID");
        $statement->execute(array(":ID" => $this->LanguageID));

        return $statement->fetch(\PDO::FETCH_ASSOC)["Flag"];
    }

}
