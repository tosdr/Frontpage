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
 * Interact with the key/value storage of the server
 */
class Config {

    private static ?PDO $Database_Connection = null;

    private static function initDB() {
        $DB = new \crisp\core\MySQL();
        self::$Database_Connection = $DB->getDBConnector();
    }

    /**
     * Checks if a Storage items exists using the specified key
     * @param string $Key the key to retrieve from the KV Config from
     * @return boolean TRUE if it exists, otherwise FALSE
     */
    public static function exists($Key) {
        if (self::$Database_Connection === null) {
            self::initDB();
        }
        $statement = self::$Database_Connection->prepare("SELECT value FROM Config WHERE `key` = :ID");
        $statement->execute(array(":ID" => $Key));
        if ($statement->rowCount() > 0) {
            return true;
        }
        return false;
    }

    /**
     * Retrieves a value from the KV Storage using the specified key
     * @param string $Key the key to retrieve from the KV Config from
     * @return string|boolean The value as string, on failure FALSE
     */
    public static function get($Key) {
        if (self::$Database_Connection === null) {
            self::initDB();
        }
        $statement = self::$Database_Connection->prepare("SELECT value, type FROM Config WHERE `key` = :ID");
        $statement->execute(array(":ID" => $Key));
        if ($statement->rowCount() > 0) {

            $Result = $statement->fetch(\PDO::FETCH_ASSOC);

            switch ($Result["type"]) {
                case 'serialized' :
                    return \unserialize($Result["value"]);
                case 'boolean':
                    return (bool) $Result["value"];
                case 'integer':
                    return (int) $Result["value"];
                case 'double':
                    return (double) $Result["value"];
                case 'string':
                case 'NULL':
                default:
                    return $Result["value"];
            }
        }
        return false;
    }

    /**
     * Get the timestamps of a key
     * @param string $Key The KVStorage key
     * @return array Containing last_changed, created_at
     */
    public static function getTimestamp(string $Key) {
        if (self::$Database_Connection === null) {
            self::initDB();
        }
        $statement = self::$Database_Connection->prepare("SELECT last_changed, created_at FROM Config WHERE `key` = :ID");
        $statement->execute(array(":ID" => $Key));
        if ($statement->rowCount() > 0) {

            $Result = $statement->fetch(\PDO::FETCH_ASSOC);


            return $Result;
        }
        return false;
    }

    /**
     * Create a new KV Storage entry using the specified key and value
     * @param string $Key the key to insert
     * @param string $Value the value to insert
     * @return boolean TRUE on success, on failure FALSE
     */
    public static function create($Key, $Value) {
        if (self::$Database_Connection === null) {
            self::initDB();
        }
        if (Config::exists($Key)) {
            return self::set($Key, $Value);
        }

        $statement = self::$Database_Connection->prepare("INSERT INTO Config (`key`) VALUES (:Key)");
        $statement->execute(array(":Key" => $Key));

        return self::set($Key, $Value);
    }

    /**
     * Delete a KV Storage entry using the specified key
     * @param string $Key the key to insert
     * @return boolean TRUE on success, on failure FALSE
     */
    public static function delete($Key) {
        if (self::$Database_Connection === null) {
            self::initDB();
        }
        $statement = self::$Database_Connection->prepare("DELETE FROM Config WHERE `key` = :Key");
        return $statement->execute(array(":Key" => $Key));
    }

    /**
     * Updates a value for a key in the KV Storage
     * @param string $Key Existing key to change the value from
     * @param string $Value The value to set
     * @return boolean TRUE on success, otherwise FALSE
     */
    public static function set($Key, $Value) {
        if (self::$Database_Connection === null) {
            self::initDB();
        }

        if(!Config::exists($Key)){
            Config::create($Key, $Value);
        }

        $Type = gettype($Value);

        if (Helper::isSerialized($Value)) {
            $Type = "serialized";
        }

        if (is_array($Value) || is_object($Value)) {
            $Type = "serialized";
            $Value = \serialize($Value);
        }
        if ($Type == "boolean") {
            $Value = ($Value ? 1 : 0);
        }


        $statement = self::$Database_Connection->prepare("UPDATE Config SET value = :val, type = :type WHERE `key` = :key");
        $statement->execute(array(":val" => $Value, ":key" => $Key, ":type" => $Type));

        return ($statement->rowCount() > 0);
    }

    /**
     * Returns all keys and values from the KV Storage
     * @param type $KV List keys as associative array?
     * @return array
     */
    public static function list($KV = false) {
        if (self::$Database_Connection === null) {
            self::initDB();
        }

        $statement = self::$Database_Connection->prepare("SELECT `key`, value FROM Config");
        $statement->execute();

        if (!$KV) {
            $Array = array();

            foreach ($statement->fetchAll(\PDO::FETCH_ASSOC) as $Item) {
                $Array[$Item["key"]] = self::get($Item["key"]);
            }

            return $Array;
        }
        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

}
