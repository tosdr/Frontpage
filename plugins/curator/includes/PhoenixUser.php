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


namespace crisp\plugin\curator;

use \PDO;
use \PDOException;
use \PDORow;
use \PDOStatement;

/**
 * Interact with a user's account
 */
class PhoenixUser {

    use \crisp\core\Hook;

    private PDO $Database_Connection;

    /**
     * The userID
     * @var int
     */
    public ?int $UserID = null;

    /**
     * If constructor has not been initiated with a numeric value, this will hold the user details as an array
     * @var array
     */
    public $User;

    public function __construct($UserID = null) {
        $DB = new \crisp\core\Postgres();
        $this->Database_Connection = $DB->getDBConnector();
        if (is_numeric($UserID)) {
            $this->UserID = $UserID;
        } else {
            $this->User = $UserID;
        }
    }

    /**
     * 
      |  Hook Name  |                Parameters               |
      |:-----------:|:---------------------------------------:|
      | beforeFetch |              array(UserID)              |
      |  afterFetch | array(<b>\crisp\plugin\curator\User</b> object) |
     * 
     * Fetches details of the user e.g. firstname, email, lastname, etc.
     * @return array associative array with the details of the user
     */
    public function fetch() {
        if ($this->UserID === null) {
            return null;
        }

        $statement = $this->Database_Connection->prepare("SELECT * FROM users WHERE id = :ID");
        $statement->execute(array(":ID" => $this->UserID));

        $Action = $statement->fetch(\PDO::FETCH_ASSOC);



        return $Action;
    }

    public function deactivate() {
        if ($this->UserID === null) {
            return null;
        }

        $statement = $this->Database_Connection->prepare("UPDATE users SET \"deactivated\" = true WHERE id = :ID");
        return $statement->execute(array(":ID" => $this->UserID));
    }

    public function verifyPassword($String) {
        return password_verify($String, $this->fetch()["encrypted_password"]);
    }

    /**
     * Creates a new session with the set user id
      |      Hook Name      |             Parameters            |
      |:-------------------:|:---------------------------------:|
      | beforeCreateSession |           array(UserID)           |
      |  afterCreateSession | array(See <b>Returns</b>, UserID) |
     * @return array|boolean Containing session data, on failure FALSE
     */
    public function createSession($Identifier = "login") {

        if ($this->UserID === null) {
            return false;
        }
        $DB = new \crisp\core\MySQL();
        $DBConnection = $DB->getDBConnector();

        if (isset($_SESSION[\crisp\core\Config::$Cookie_Prefix . "session_$Identifier"])) {
            unset($_SESSION[\crisp\core\Config::$Cookie_Prefix . "session_$Identifier"]);
        }

        $Token = \crisp\core\Crypto::UUIDv4();

        $statement = $DBConnection->prepare('INSERT INTO sessions (token, "user", identifier) VALUES (:Token, :User, :Identifier)');
        $result = $statement->execute(array(":User" => $this->UserID, ":Token" => $Token, ":Identifier" => $Identifier));

        if (!$result) {
            return false;
        }

        $Session = array(
            "identifier" => $Identifier,
            "token" => $Token,
            "user" => $this->UserID
        );

        $_SESSION[\crisp\core\Config::$Cookie_Prefix . "session_$Identifier"] = $Session;

        return $Session;
    }

    /**
     * Destroy a user's current session and log them out
      |          Hook Name          |             Parameters            |
      |:---------------------------:|:---------------------------------:|
      | beforeDestroyCurrentSession |           array(UserID)           |
      |  afterDestroyCurrentSession | array(See <b>Returns</b>, UserID) |
     * @return boolean TRUE if the session has been successfully destroyed otherwise FALSE
     */
    public function destroyCurrentSession($Identifier = "login") {
        if ($this->UserID === null) {
            return null;
        }
        $this->broadcastHook("beforeDestroyCurrentSession", $this->UserID);

        if (!isset($_SESSION[\crisp\core\Config::$Cookie_Prefix . "session_$Identifier"])) {
            return false;
        }

        $statement = $this->Database_Connection->prepare('DELETE FROM sessions WHERE token = :Token AND "user" = :User');
        $Action = $statement->execute(array(":User" => $this->UserID, ":Token" => $_SESSION[\crisp\core\Config::$Cookie_Prefix . "session_$Identifier"]["token"]));



        unset($_SESSION[\crisp\core\Config::$Cookie_Prefix . "session_$Identifier"]);


        return $Action;
    }

    /**
     * Checks if the session of the current user is valid
      |       Hook Name      |             Parameters            |
      |:--------------------:|:---------------------------------:|
      | beforeIsSessionValid |           array(UserID)           |
      |  afterIsSessionValid | array(See <b>Returns</b>, UserID) |
     * @return boolean TRUE if session is valid, otherwise FALSE
     */
    public function isSessionValid($Identifier = "login") {
        if ($this->UserID === null) {
            return null;
        }
        $DB = new \crisp\core\MySQL();
        $DBConnection = $DB->getDBConnector();

        if (!isset($_SESSION[\crisp\core\Config::$Cookie_Prefix . "session_$Identifier"])) {
            return false;
        }

        $Token = $_SESSION[\crisp\core\Config::$Cookie_Prefix . "session_$Identifier"]["token"];

        $statement = $DBConnection->prepare('SELECT * FROM sessions WHERE token = :Token AND "user" = :ID');
        $statement->execute(array(":ID" => $this->UserID, ":Token" => $Token));

        $Action = ($statement->rowCount() > 0);


        return $Action;
    }

}
