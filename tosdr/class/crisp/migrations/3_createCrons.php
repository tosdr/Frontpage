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


namespace crisp\migrations;

class CreateCrons extends \crisp\core\Migrations {

    public function run() {
        try {
            $this->begin();



            $this->createTable("Cron",
                    array("ID", $this::DB_INTEGER, "NOT NULL SERIAL"),
                    array("Type", $this::DB_VARCHAR, "NOT NULL"),
                    array("ScheduledAt", $this::DB_TIMESTAMP, "NOT NULL DEFAULT CURRENT_TIMESTAMP"),
                    array("CreatedAt", $this::DB_TIMESTAMP, "DEFAULT NULL"),
                    array("FinishedAt", $this::DB_TIMESTAMP, "DEFAULT NULL"),
                    array("UpdatedAt", $this::DB_TIMESTAMP, "DEFAULT NULL"),
                    array("StartedAt", $this::DB_TIMESTAMP, "DEFAULT NULL"),
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
