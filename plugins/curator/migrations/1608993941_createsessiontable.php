<?php

namespace crisp\migrations;

class createsessiontable extends \crisp\core\Migrations {

    public function run() {
        try {
            $this->begin();
            $this->createTable("sessions",
                    array("id", $this::DB_INTEGER, "NOT NULL SERIAL"),
                    array("token", $this::DB_VARCHAR, "NOT NULL"),
                    array('"user"', $this::DB_INTEGER, "NOT NULL"),
                    array("Createdat", $this::DB_TIMESTAMP, "NOT NULL DEFAULT CURRENT_TIMESTAMP"),
                    array("identifier", $this::DB_VARCHAR, "NOT NULL DEFAULT 'login'")
            );
            $this->addIndex("sessions", "token", $this::DB_PRIMARYKEY);
            return $this->end();
        } catch (\Exception $ex) {
            echo $ex->getMessage() . PHP_EOL;
            $this->rollback();
            return false;
        }
    }

}
