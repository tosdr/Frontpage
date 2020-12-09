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

namespace crisp\plugin\admin;

use \PDO;
use \PDOException;
use \PDORow;
use \PDOStatement;

/**
 * Interact with a user's account
 */
class User {

    use \crisp\core\Hook;

    private PDO $Database_Connection;

    /**
     * The userID
     * @var int
     */
    public int $UserID;

    /**
     * If constructor has not been initiated with a numeric value, this will hold the user details as an array
     * @var array
     */
    public $User;

    public function __construct($UserID = null) {
        $DB = new \crisp\core\MySQL();
        $this->Database_Connection = $DB->getDBConnector();
        if (is_numeric($UserID)) {
            $this->UserID = $UserID;
        } else {
            $this->User = $UserID;
        }
    }

    /**
     * 
     * | Hook Name | Triggered At |   Parameters  |
     * |:---------:|:------------:|:-------------:|
     * |  testHook |    Always    | array(String) |
     * 
     * Test your plugin hook receiver using a test hook.
     * 
     */
    public function testHook() {
        $this->broadcastHook("testHook", "Houston, connection established!");
    }

    /**
     * 
      |        Hook Name       |             Parameters            |
      |:----------------------:|:---------------------------------:|
      | beforeBeginTransaction |           array(UserID)           |
      |  afterBeginTransaction | array(See <b>Returns</b>, UserID) |
     * 
     * Enables the MySQL Transaction
     * @see commitTransaction
     * @see rollBackTransaction
     * @return boolean TRUE if transaction could be enabled otherwise FALSE
     */
    public function enableTransaction() {
        $this->broadcastHook("beforeBeginTransaction", $this->UserID);
        $Action = $this->Database_Connection->beginTransaction();
        $this->broadcastHook("afterBeginTransaction", $Action, $this->UserID);
        return $Action;
    }

    /**
     * 
      |        Hook Name        |             Parameters            |
      |:-----------------------:|:---------------------------------:|
      | beforeCommitTransaction |           array(UserID)           |
      |  afterCommitTransaction | array(See <b>Returns</b>, UserID) |
     * 
     * Commits the MySQL Transaction
     * @see enableTransaction()
     * @return boolean TRUE if transaction could be committed otherwise FALSE
     */
    public function commitTransaction() {
        $this->broadcastHook("beforeCommitTransaction", $this->UserID);
        $Action = $this->Database_Connection->commit();
        $this->broadcastHook("afterCommitTransaction", $Action, $this->UserID);
        return $Action;
    }

    /**
     * 
      |         Hook Name         |             Parameters            |
      |:-------------------------:|:---------------------------------:|
      | beforeRollBackTransaction |           array(UserID)           |
      |  afterRollBackTransaction | array(See <b>Returns</b>, UserID) |
     * 
     * Rolls back a previously initiated MySQL Transaction
     * @see enableTransaction()
     * @return boolean TRUE if transaction could be rolledback otherwise FALSE
     */
    public function rollBackTransaction() {
        $this->broadcastHook("beforeRollBackTransaction", $this->UserID);
        $Action = $this->Database_Connection->rollBack();
        $this->broadcastHook("afterRollBackTransaction", $Action, $this->UserID);
        return $Action;
    }

    /**
     * 
      |  Hook Name  |                Parameters               |
      |:-----------:|:---------------------------------------:|
      | beforeFetch |              array(UserID)              |
      |  afterFetch | array(<b>\crisp\plugin\admin\User</b> object) |
     * 
     * Fetches details of the user e.g. firstname, email, lastname, etc.
     * @return array associative array with the details of the user
     */
    public function fetch() {
        if ($this->UserID === null) {
            return null;
        }

        $this->broadcastHook("beforeFetch", $this->UserID);
        $statement = $this->Database_Connection->prepare("SELECT * FROM Users WHERE ID = :ID");
        $statement->execute(array(":ID" => $this->UserID));

        $Action = $statement->fetch(\PDO::FETCH_ASSOC);

        $this->broadcastHook("afterFetch", new \crisp\plugin\admin\User($Action["ID"]));


        return $Action;
    }

    public function verifyPassword($String) {
        return password_verify($String, $this->fetch()["Password"]);
    }

    /**
     * 
      |      Hook Name     |             Parameters            |
      |:------------------:|:---------------------------------:|
      | beforeSetFirstname |           array(UserID)           |
      |  afterSetFirstname | array(See <b>Returns</b>, UserID) |
     * 
     * Sets the firstname of the user
     * @param string $Firstname The firstname of the user to set to
     * @see getFirstname
     * @return boolean TRUE if the firstname has been successfully edited otherwise FALSE
     */
    public function setFirstname(string $Firstname) {
        if ($this->UserID === null) {
            return null;
        }

        $this->broadcastHook("beforeSetFirstname", $this->UserID);
        $statement = $this->Database_Connection->prepare("UPDATE Users SET Firstname = :Firstname WHERE ID = :ID");

        $Action = $statement->execute(array(":Firstname" => $Firstname, ":ID" => $this->UserID));
        $this->broadcastHook("afterSetFirstname", $Action, $this->UserID);

        return $Action;
    }

    /**
     * 
      |      Hook Name     |             Parameters            |
      |:------------------:|:---------------------------------:|
      | beforeGetFirstname |           array(UserID)           |
      |  afterGetFirstname | array(See <b>Returns</b>, UserID) |
     * Gets the firstname of the user
     * @see setFirstname
     * @return string|null Firstname of the user, on failure NULL
     */
    public function getFirstname() {
        if ($this->UserID === null) {
            return null;
        }
        $this->broadcastHook("beforeGetFirstname", $this->UserID);

        $statement = $this->Database_Connection->prepare("SELECT Firstname FROM Users WHERE ID = :ID");
        $statement->execute(array(":ID" => $this->UserID));

        if ($statement->rowCount() === 0) {
            $this->broadcastHook("afterGetFirstname", null, $this->UserID);
            return null;
        }

        $Action = $statement->fetch(\PDO::FETCH_ASSOC)["Firstname"];

        $this->broadcastHook("afterGetFirstname", $Action, $this->UserID);
        return $Action;
    }

    /**
     * 
      |     Hook Name     |             Parameters            |
      |:-----------------:|:---------------------------------:|
      | beforeSetLastname |           array(UserID)           |
      |  afterSetLastname | array(See <b>Returns</b>, UserID) |
     * Sets the lastname of the user
     * @param string $Lastname The lastname of the user to set so
     * @see getLastname
     * @return boolean TRUE if the lastname has been successfully edited otherwise FALSE
     */
    public function setLastname($Lastname) {
        if ($this->UserID === null) {
            return null;
        }
        $this->broadcastHook("beforeSetLastname", $this->UserID);
        $statement = $this->Database_Connection->prepare("UPDATE Users SET Lastname = :Lastname WHERE ID = :ID");
        $Action = $statement->execute(array(":Lastname" => $Lastname, ":ID" => $this->UserID));

        $this->broadcastHook("afterSetLastname", $Action, $this->UserID);

        return $Action;
    }

    /**
     * 
      |     Hook Name     |             Parameters            |
      |:-----------------:|:---------------------------------:|
      | beforeGetLastname |           array(UserID)           |
      |  afterGetLastname | array(See <b>Returns</b>, UserID) |
     * Gets the lastname of the user
     * @see setLastname
     * @return string|null Lastname of the user, on failure NULL
     */
    public function getLastname() {
        if ($this->UserID === null) {
            return null;
        }

        $this->broadcastHook("beforeGetLastname", $this->UserID);
        $statement = $this->Database_Connection->prepare("SELECT Lastname FROM Users WHERE ID = :ID");
        $statement->execute(array(":ID" => $this->UserID));

        if ($statement->rowCount() === 0) {
            $this->broadcastHook("afterGetLastname", null, $this->UserID);
            return null;
        }
        $Action = $statement->fetch(\PDO::FETCH_ASSOC)["Lastname"];
        $this->broadcastHook("afterGetLastname", $Action, $this->UserID);

        return $Action;
    }

    /**
     * 
      |    Hook Name   |             Parameters            |
      |:--------------:|:---------------------------------:|
      | beforeSetEmail |           array(UserID)           |
      |  afterSetEmail | array(See <b>Returns</b>, UserID) |
     * Sets the email of the user
     * @param string $Email The email address of the user to set to
     * @see getEmail
     * @return boolean TRUE if the email has been successfully edited otherwise FALSE
     * @throws \crisp\exceptions\EmailFilterException If email is not valid
     */
    public function setEmail(string $Email) {
        if ($this->UserID === null) {
            return null;
        }
        $this->broadcastHook("beforeSetEmail", $this->UserID);


        $statement = $this->Database_Connection->prepare("UPDATE Users SET Email = :Email WHERE ID = :ID");
        $Action = $statement->execute(array(":Email" => $Email, ":ID" => $this->UserID));

        $this->broadcastHook("afterSetEmail", $Action, $this->UserID);
        return $Action;
    }

    /**
      |    Hook Name   |             Parameters            |
      |:--------------:|:---------------------------------:|
      | beforeGetEmail |           array(UserID)           |
      |  afterGetEmail | array(See <b>Returns</b>, UserID) |
     * Gets the email of the user
     * @see setEmail
     * @return string|null email of the user, on failure/or if account is dummy NULL
     */
    public function getEmail() {
        if ($this->UserID === null) {
            return null;
        }

        $this->broadcastHook("beforeGetEmail", $this->UserID);
        $statement = $this->Database_Connection->prepare("SELECT Email FROM Users WHERE ID = :ID");
        $statement->execute(array(":ID" => $this->UserID));

        if ($statement->rowCount() === 0) {
            $this->broadcastHook("afterGetEmail", null, $this->UserID);
            return null;
        }
        $Action = $statement->fetch(\PDO::FETCH_ASSOC)["Email"];
        $this->broadcastHook("afterGetEmail", $Action, $this->UserID);
        return $Action;
    }

    /**
     * 
      |      Hook Name      |             Parameters            |
      |:-------------------:|:---------------------------------:|
      | beforeAttemptDelete |           array(UserID)           |
      |  afterAttemptDelete | array(See <b>Returns</b>, UserID) |
     * Attempts to delete the user, useful if dummy.
     * @return boolean TRUE if user has been deleted, otherwise FALSE
     */
    public function attemptDelete() {
        if ($this->UserID === null) {
            return null;
        }

        $this->broadcastHook("beforeAttemptDelete", $this->UserID);
        $statement = $this->Database_Connection->prepare("DELETE FROM Users WHERE ID = :ID");
        $Action = $statement->execute(array(":ID" => $this->UserID));

        if ($statement->rowCount() === 0) {
            $this->broadcastHook("afterAttemptDelete", false, $this->UserID);
            return null;
        }
        $this->broadcastHook("afterAttemptDelete", $Action, $this->UserID);
        return $Action;
    }

    /**
     * 
      |     Hook Name     |             Parameters            |
      |:-----------------:|:---------------------------------:|
      | beforeSetPassword |           array(UserID)           |
      |  afterSetPassword | array(See <b>Returns</b>, UserID) |
     * Sets the password of the user and automatically encrypts it.
     * @param string $Password The plain text password which should be set as the new password
     * @see \crisp\api\Authentication::generatePassword()
     * @return boolean TRUE if the firstname has been successfully edited otherwise FALSE
     */
    public function setPassword(string $Password) {
        if ($this->UserID === null) {
            return null;
        }
        $this->broadcastHook("beforeSetPassword", $this->UserID);


        $statement = $this->Database_Connection->prepare("UPDATE Users SET Password = :Password WHERE ID = :ID");
        $Action = $statement->execute(array(":Password" => password_hash($Password, PASSWORD_BCRYPT), ":ID" => $this->UserID));
        $this->broadcastHook("afterSetPassword", $Action, $this->UserID);
        return $Action;
    }

    /**
     * 
      |    Hook Name   |             Parameters            |
      |:--------------:|:---------------------------------:|
      | beforeSetLevel |           array(UserID)           |
      |  afterSetLevel | array(See <b>Returns</b>, UserID) |
     * Sets the user's account level
     * @param int $Level
     * @see getLevel
     * @return boolean TRUE if the level has been successfully edited otherwise FALSE
     */
    public function setLevel(int $Level) {
        if ($this->UserID === null) {
            return null;
        }
        $this->broadcastHook("beforeSetLevel", $this->UserID);

        $statement = $this->Database_Connection->prepare("UPDATE Users SET Level = :Level WHERE ID = :ID");
        $Action = $statement->execute(array(":Level" => $Level, ":ID" => $this->UserID));


        $this->broadcastHook("afterSetLevel", $Action, $this->UserID);
        return $Action;
    }

    /**
     * 
      |    Hook Name   |             Parameters            |
      |:--------------:|:---------------------------------:|
      | beforeGetLevel |           array(UserID)           |
      |  afterGetLevel | array(See <b>Returns</b>, UserID) |
     * 
     * Gets the level of the user
     *
     * Account Levels:<br><br>
     * -1 = Dummy Account<br>
     * 0 = Guest Account (Reserved)<br>
     * 1 = Normal Account<br>
     * 2 = Artist Account<br>
     * 3 = Admin Account<br>
     *
     * @see setLevel
     * @return int|null level of the user, on failure NULL
     */
    public function getLevel() {
        if ($this->UserID === null) {
            return null;
        }
        $this->broadcastHook("beforeGetLevel", $this->UserID);

        $statement = $this->Database_Connection->prepare("SELECT Level FROM Users WHERE ID = :ID");
        $statement->execute(array(":ID" => $this->UserID));

        if ($statement->rowCount() === 0) {
            $this->broadcastHook("afterGetLevel", null, $this->UserID);
            return null;
        }
        $Action = (int) $statement->fetch(\PDO::FETCH_ASSOC)["Level"];


        $this->broadcastHook("afterGetLevel", $Action, $this->UserID);

        return $Action;
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
            return null;
        }
        $this->broadcastHook("beforeCreateSession", $this->UserID);

        if (isset($_SESSION[\crisp\core\Config::$Cookie_Prefix . "session_$Identifier"])) {
            return false;
        }

        $Token = \crisp\core\Crypto::UUIDv4();

        $statement = $this->Database_Connection->prepare("INSERT INTO Sessions (Token, User, Identifier) VALUES (:Token, :User, :Identifier)");
        $statement->execute(array(":User" => $this->UserID, ":Token" => $Token, ":Identifier" => $Identifier));

        if ($statement->rowCount() === 0) {
            return false;
        }

        $Session = array(
            "Identifier" => $Identifier,
            "Token" => $Token,
            "User" => $this->UserID
        );

        $_SESSION[\crisp\core\Config::$Cookie_Prefix . "session_$Identifier"] = $Session;

        $this->broadcastHook("afterCreateSession", $Session, $this->UserID);

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

        $statement = $this->Database_Connection->prepare("DELETE FROM Sessions WHERE Token = :Token AND User = :User");
        $Action = $statement->execute(array(":User" => $this->UserID, ":Token" => $_SESSION[\crisp\core\Config::$Cookie_Prefix . "session_$Identifier"]["Token"]));



        unset($_SESSION[\crisp\core\Config::$Cookie_Prefix . "session_$Identifier"]);

        $this->broadcastHook("afterDestroyCurrentSession", $Action, $this->UserID);

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
        $this->broadcastHook("beforeIsSessionValid", $this->UserID);

        if (!isset($_SESSION[\crisp\core\Config::$Cookie_Prefix . "session_$Identifier"])) {
            return false;
        }

        $Token = $_SESSION[\crisp\core\Config::$Cookie_Prefix . "session_$Identifier"]["Token"];

        $statement = $this->Database_Connection->prepare("SELECT * FROM Sessions WHERE Token = :Token AND User = :ID");
        $statement->execute(array(":ID" => $this->UserID, ":Token" => $Token));

        $Action = ($statement->rowCount() > 0);


        $this->broadcastHook("afterIsSessionValid", $Action, $this->UserID);

        return $Action;
    }

}
