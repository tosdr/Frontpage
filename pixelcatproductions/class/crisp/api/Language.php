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

use crisp\api\lists\Languages;
use crisp\core\MySQL;
use PDO;

/**
 * Interact with a language
 */
class Language extends Languages {

    private PDO $Database_Connection;
    public int $LanguageID;
    public mixed $Language;

    public function __construct($LanguageID) {
        $DB = new MySQL();
        $this->Database_Connection = $DB->getDBConnector();
        if (is_numeric($LanguageID)) {
            $this->LanguageID = $LanguageID;
        } else {
            $this->Language = $LanguageID;
        }
    }

    /**
     * Fetches a language's details
     * @return array|null
     */
    public function fetch(): ?array
    {
        if ($this->LanguageID === null) {
            return null;
        }

        $statement = $this->Database_Connection->prepare("SELECT * FROM Languages WHERE ID = :ID");
        $statement->execute(array(":ID" => $this->LanguageID));

        return $statement->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Enables a language
     * @return bool|null
     * @see disable
     */
    public function enable(): ?bool
    {
        if ($this->LanguageID === null) {
            return null;
        }

        $statement = $this->Database_Connection->prepare("UPDATE Languages SET Enabled = 1 WHERE ID = :ID");
        return $statement->execute(array(":ID" => $this->LanguageID));
    }

    /**
     * Disables a language
     * @return bool|null
     * @see enable
     */
    public function disable(): ?bool
    {
        if ($this->LanguageID === null) {
            return null;
        }

        $statement = $this->Database_Connection->prepare("UPDATE Languages SET Enabled = 0 WHERE ID = :ID");
        return $statement->execute(array(":ID" => $this->LanguageID));
    }

    /**
     * Checks wether a language is enabled or not
     * @return bool|null
     */
    public function isEnabled(): ?bool
    {
        if ($this->LanguageID === null) {
            return null;
        }

        $statement = $this->Database_Connection->prepare("SELECT Enabled FROM Languages WHERE ID = :ID");
        $statement->execute(array(":ID" => $this->LanguageID));

        return $statement->fetch(PDO::FETCH_ASSOC)["enabled"];
    }

    /**
     * Check if the language exists in the database
     * @return bool|null
     */
    public function exists(): ?bool
    {
        if ($this->LanguageID === null) {
            return null;
        }

        $statement = $this->Database_Connection->prepare("SELECT ID FROM Languages WHERE ID = :ID");
        $statement->execute(array(":ID" => $this->LanguageID));

        return ($statement->rowCount() != 0);
    }

    /**
     * Sets a new name for the language
     * @param string $Name The new name of the language
     * @return bool|null TRUE if successfully set, otherwise false
     */
    public function setName(string $Name): ?bool
    {
        if ($this->LanguageID === null) {
            return null;
        }

        $statement = $this->Database_Connection->prepare("UPDATE Languages SET Name = :Name WHERE ID = :ID");
        return $statement->execute(array(":Name" => $Name, ":ID" => $this->LanguageID));
    }

    /**
     * Gets the name of the language
     * @return string|null
     */
    public function getName(): ?string
    {
        if ($this->LanguageID === null) {
            return null;
        }

        $statement = $this->Database_Connection->prepare("SELECT Name FROM Languages WHERE ID = :ID");
        $statement->execute(array(":ID" => $this->LanguageID));

        return $statement->fetch(PDO::FETCH_ASSOC)["name"];
    }

    /**
     * Sets the code of the language
     * @param string $Code The new language code
     * @return bool|null TRUE if successfully set, otherwise false
     */
    public function setCode(string $Code): ?bool
    {
        if ($this->LanguageID === null) {
            return null;
        }

        $statement = $this->Database_Connection->prepare("UPDATE Languages SET Code = :Code WHERE ID = :ID");
        return $statement->execute(array(":Code" => $Code, ":ID" => $this->LanguageID));
    }

    /**
     * Gets the code of a language
     * @return bool|null
     */
    public function getCode(): ?bool
    {
        if ($this->LanguageID === null) {
            return null;
        }

        $statement = $this->Database_Connection->prepare("SELECT Code FROM Languages WHERE ID = :ID");
        $statement->execute(array(":ID" => $this->LanguageID));

        return $statement->fetch(PDO::FETCH_ASSOC)["code"];
    }

    /**
     * Sets the new native name of the language
     * @param string $NativeName The new native name
     * @return bool|null TRUE if successfully set, otherwise false
     */
    public function setNativeName(string $NativeName): ?bool
    {
        if ($this->LanguageID === null) {
            return null;
        }

        $statement = $this->Database_Connection->prepare("UPDATE Languages SET NativeName = :NativeName WHERE ID = :ID");
        return $statement->execute(array(":NativeName" => $NativeName, ":ID" => $this->LanguageID));
    }

    /**
     * Gets the native name of a language
     * @return string|null
     */
    public function getNativeName(): ?string
    {
        if ($this->LanguageID === null) {
            return null;
        }

        $statement = $this->Database_Connection->prepare("SELECT NativeName FROM Languages WHERE ID = :ID");
        $statement->execute(array(":ID" => $this->LanguageID));

        return $statement->fetch(PDO::FETCH_ASSOC)["nativename"];
    }

    /**
     * Delete a translation key
     * @param string $Key The translation key
     * @return bool|null
     */
    public function deleteTranslation(string $Key): ?bool
    {
        if ($this->LanguageID === null) {
            return null;
        }

        $statement = $this->Database_Connection->prepare("DELETE FROM Translations WHERE key = :key");
        return $statement->execute(array(":key" => $Key));
    }

    /**
     * Edit a translation key
     * @param string $Key The translation key
     * @param string $Value The new value to set
     * @return bool
     */
    public function editTranslation(string $Key, string $Value) {
        if ($this->LanguageID === null) {
            return null;
        }

        $Code = $this->getCode();
        $statement = $this->Database_Connection->prepare("UPDATE Translations SET $Code = :value WHERE key = :key");
        return $statement->execute(array(":key" => $Key, ":value" => $Value));
    }

    /**
     * Create a new translation key
     * @param string $Key The translation key to create
     * @param string $Value The translation text
     * @return bool
     */
    public function newTranslation(string $Key, string $Value, string $Language = "en") {
        if ($this->LanguageID === null) {
            return null;
        }
        $Translation = new Translation($Language);
        
        if ($Translation->get($Key) === $Value) {
            return false;
        }
        if (Translation::exists($Key)) {
            return $this->editTranslation($Key, $Value);
        }

        $Code = $this->getCode();
        $statement = $this->Database_Connection->prepare("INSERT INTO Translations (key, $Code) VALUES (:key, :value)");
        return $statement->execute(array(":key" => $Key, ":value" => $Value));
    }

    /**
     * Sets the flag icon of a language
     * @param string $Flag The flag icon name, see Themes
     * @return boolean TRUE if successfully set, otherwise false
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
     * @return string The current path of the flag
     */
    public function getFlag() {
        if ($this->LanguageID === null) {
            return null;
        }

        $statement = $this->Database_Connection->prepare("SELECT Flag FROM Languages WHERE ID = :ID");
        $statement->execute(array(":ID" => $this->LanguageID));

        return $statement->fetch(PDO::FETCH_ASSOC)["flag"];
    }

}
