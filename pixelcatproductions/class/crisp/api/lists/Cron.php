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

namespace crisp\api\lists;

use \PDO;
use \PDOException;
use \PDORow;
use \PDOStatement;

/**
 * Interact with all cron jobs stored on the server
 */
class Cron {

    private static ?PDO $Database_Connection = null;

    public function __construct() {
        self::initDB();
    }

    /**
     * Initializes the DB
     */
    private static function initDB() {
        $DB = new \crisp\core\MySQL();
        self::$Database_Connection = $DB->getDBConnector();
    }

    /**
     * Retrieve a list of cron jobs
     * @param int $Limit How many do you like to retrieve
     * @return array Contains cron jobs
     */
    public static function fetchAll(int $Limit = 2) {
        if (self::$Database_Connection === null) {
            self::initDB();
        }
        $statement = self::$Database_Connection->prepare("SELECT * FROM Cron ORDER BY ID DESC,Finished ASC LIMIT $Limit;");
        $statement->execute();

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Retrieve a list of unprocessed cron jobs which should start by now
     * @param int $Limit How many do you like to retrieve
     * @return array Contains cron jobs
     */
    public static function fetchUnprocessedSchedule(int $Limit = 2) {
        if (self::$Database_Connection === null) {
            self::initDB();
        }
        $statement = self::$Database_Connection->prepare("SELECT * FROM Cron WHERE ScheduledAt < NOW() AND Finished = 0 AND Started = 0 AND Canceled = 0 AND Failed = 0 ORDER BY ScheduledAt ASC LIMIT $Limit;");
        $statement->execute();

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Retrieve a list of all unprocessed cron jobs
     * @param int $Limit How many do you like to retrieve
     * @return array Contains cron jobs
     */
    public static function fetchUnprocessed(int $Limit = 2) {
        if (self::$Database_Connection === null) {
            self::initDB();
        }
        $statement = self::$Database_Connection->prepare("SELECT * FROM Cron WHERE Finished = 0 ORDER BY ID ASC LIMIT $Limit;");
        $statement->execute();

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Fetch details of a specific cron job
     * @param int $ID The ID of a cron job
     * @return array Contains cron details
     */
    public static function fetch(int $ID) {
        if (self::$Database_Connection === null) {
            self::initDB();
        }
        $statement = self::$Database_Connection->prepare("SELECT * FROM Cron WHERE ID = :ID;");
        $statement->execute(array(":ID" => $ID));

        return $statement->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Delete processed, finished or failed cronjobs
     * @return Boolean TRUE if action successful
     */
    public static function deleteOld() {
        if (self::$Database_Connection === null) {
            self::initDB();
        }
        $statement = self::$Database_Connection->prepare("DELETE FROM Cron WHERE Finished = 1 OR Canceled = 1 OR Failed = 1 AND ExecuteOnce = 0;");
        return $statement->execute();
    }

    public static function deleteByPlugin($Plugin) {
        if (self::$Database_Connection === null) {
            self::initDB();
        }
        $statement = self::$Database_Connection->prepare("DELETE FROM Cron WHERE Plugin = :Plugin;");
        return $statement->execute(array(":Plugin" => $Plugin));
    }

    /**
     * Create a new cron job
     * @param string $Type The name of the cronjon, all lowercase, no spaces
     * @param string $Data The data which should be sent to the cron
     * @param string $Interval In which interval should the cron be executed?
     * @return int The ID of the Cron
     */
    public static function create(string $Type, string $Data, string $Interval = "2 MINUTE", string $Plugin = null, bool $ExecuteOnce = false) {
        if (self::$Database_Connection === null) {
            self::initDB();
        }
        $statement = self::$Database_Connection->prepare("INSERT INTO Cron (Type, Data, ScheduledAt, `Interval`, Plugin, ExecuteOnce) VALUES (:Type, :Data, (NOW() + INTERVAL $Interval), :Interval, :Plugin, :ExecuteOnce)");
        $statement->execute(array(":Type" => $Type, ":Data" => $Data, ":Interval" => $Interval, ":Plugin" => $Plugin, ":ExecuteOnce" => ($ExecuteOnce ? 1 : 0)));

        return self::$Database_Connection->lastInsertId();
    }

    /**
     * Mark a specific cronjob as started
     * @param int $ID The ID of a cron job
     * @return Boolean TRUE if action successful
     */
    public static function markAsStarted(int $ID) {
        if (self::$Database_Connection === null) {
            self::initDB();
        }
        $statement = self::$Database_Connection->prepare("UPDATE Cron SET StartedAt = NOW(), Started = 1 WHERE ID = :ID");
        return $statement->execute(array(":ID" => $ID));
    }

    /**
     * Mark a specific cronjob as canceled
     * @param int $ID The ID of a cron job
     * @return Boolean TRUE if action successful
     */
    public static function markAsCanceled(int $ID) {
        if (self::$Database_Connection === null) {
            self::initDB();
        }
        $statement = self::$Database_Connection->prepare("UPDATE Cron SET Canceled = 1, Started = 0, Finished = 0 WHERE ID = :ID");
        $Job = \crisp\api\lists\Cron::fetch($ID);
        $PluginData = json_decode($Job["Data"]);
        if (!$Job["ExecuteOnce"]) {
            \crisp\api\lists\Cron::create("execute_plugin_cron", json_encode(array("data" => $PluginData->data, "name" => $PluginData->name)), $Job["Interval"], $Job["Plugin"]);
        }
        return $statement->execute(array(":ID" => $ID));
    }

    /**
     * Mark a specific cronjob as finished
     * @param int $ID The ID of a cron job
     * @return Boolean TRUE if action successful
     */
    public static function markAsFinished(int $ID) {
        if (self::$Database_Connection === null) {
            self::initDB();
        }
        $statement = self::$Database_Connection->prepare("UPDATE Cron SET Finished = 1, Started = 0, Canceled = 0, FinishedAt = NOW() WHERE ID = :ID");

        $Job = \crisp\api\lists\Cron::fetch($ID);
        $PluginData = json_decode($Job["Data"]);
        if (!$Job["ExecuteOnce"]) {
            \crisp\api\lists\Cron::create("execute_plugin_cron", json_encode(array("data" => $PluginData->data, "name" => $PluginData->name)), $Job["Interval"], $Job["Plugin"]);
        }
        return $statement->execute(array(":ID" => $ID));
    }

    /**
     * Mark a specific cronjob as failed
     * @param int $ID The ID of a cron job
     * @return Boolean TRUE if action successful
     */
    public static function markAsFailed(int $ID) {
        if (self::$Database_Connection === null) {
            self::initDB();
        }
        $statement = self::$Database_Connection->prepare("UPDATE Cron SET Failed = 1, Started = 0, Canceled = 0, Finished = 0 WHERE ID = :ID");

        $Job = \crisp\api\lists\Cron::fetch($ID);
        $PluginData = json_decode($Job["Data"]);
        if (!$Job["ExecuteOnce"]) {
            \crisp\api\lists\Cron::create("execute_plugin_cron", json_encode(array("data" => $PluginData->data, "name" => $PluginData->name)), $Job["Interval"], $Job["Plugin"]);
        }
        return $statement->execute(array(":ID" => $ID));
    }

    /**
     * Edit the log of a cronjob
     * @param int $ID The ID of a cron job
     * @param string $Log The Text to set
     * @return Boolean TRUE if action successful
     */
    public static function setLog(int $ID, string $Log) {
        if (self::$Database_Connection === null) {
            self::initDB();
        }
        $statement = self::$Database_Connection->prepare("UPDATE Cron SET Log = :Log WHERE ID = :ID");
        return $statement->execute(array(":ID" => $ID, ":Log" => $Log));
    }

}
