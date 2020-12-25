<?php

namespace crisp\migrations;

class createcrons extends \crisp\core\Migrations {

    public function run() {
        try {
            $this->begin();



            $this->createTable("Cron",
                    array("ID", $this::DB_INTEGER, "NOT NULL AUTO_INCREMENT"),
                    array("Type", $this::DB_VARCHAR, "NOT NULL"),
                    array("ScheduledAt", $this::DB_TIMESTAMP, "NOT NULL DEFAULT CURRENT_TIMESTAMP()"),
                    array("CreatedAt", $this::DB_TIMESTAMP, "NOT NULL DEFAULT '0000-00-00 00:00:00'"),
                    array("FinishedAt", $this::DB_TIMESTAMP, "NOT NULL DEFAULT '0000-00-00 00:00:00'"),
                    array("UpdatedAt", $this::DB_TIMESTAMP, "NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE current_timestamp()"),
                    array("StartedAt", $this::DB_TIMESTAMP, "NOT NULL DEFAULT '0000-00-00 00:00:00'"),
                    array("Finished", $this::DB_BOOL, "NOT NULL DEFAULT 0"),
                    array("Started", $this::DB_BOOL, "NOT NULL DEFAULT 0"),
                    array("Canceled", $this::DB_BOOL, "NOT NULL DEFAULT 0"),
                    array("Failed", $this::DB_BOOL, "NOT NULL DEFAULT 0"),
                    array("Data", $this::DB_LONGTEXT, "DEFAULT NULL"),
                    array("Log", $this::DB_LONGTEXT, "DEFAULT NULL"),
                    array("Interval", $this::DB_VARCHAR, "DEFAULT '5 MINUTE'"),
                    array("Plugin", $this::DB_VARCHAR, "DEFAULT NULL")
            );

            return $this->end();
        } catch (\Exception $ex) {
            echo $ex->getMessage() . PHP_EOL;
            $this->rollback();
            return false;
        }
    }

}
