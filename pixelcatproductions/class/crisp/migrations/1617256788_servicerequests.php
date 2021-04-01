<?php

namespace crisp\migrations;

class servicerequests extends \crisp\core\Migrations {

    public function run() {
        try {
            $this->begin();
            $this->createTable("service_requests",
                    array("id", $this::DB_INTEGER, "NOT NULL SERIAL"),
                    array("name", $this::DB_VARCHAR, "NOT NULL"),
                    array("domains", $this::DB_TEXT, "NOT NULL"),
                    array("documents", $this::DB_TEXT, "NOT NULL"),
                    array("wikipedia", $this::DB_VARCHAR, "NOT NULL"),
            );
            return $this->end();
        } catch (\Exception $ex) {
            echo $ex->getMessage() . PHP_EOL;
            $this->rollback();
            return false;
        }
    }

}
