<?php

namespace crisp\migrations;

class createcrashes extends \crisp\core\Migrations {

    public function run() {
        try {
            $this->begin();

            $this->createTable("Crashes",
                    array("ReferenceID", $this::DB_VARCHAR, "NOT NULL"),
                    array("HttpStatusCode", $this::DB_INTEGER, "NOT NULL DEFAULT 500"),
                    array("Traceback", $this::DB_TEXT, "DEFAULT NULL"),
                    array("Summary", $this::DB_TEXT, "DEFAULT NULL"),
                    array("CreatedAt", $this::DB_TIMESTAMP, "NOT NULL DEFAULT CURRENT_TIMESTAMP()")
            );
            $this->addIndex("Crashes", "ReferenceID", $this::DB_PRIMARYKEY);

            return $this->end();
        } catch (\Exception $ex) {
            echo $ex->getMessage() . PHP_EOL;
            $this->rollback();
            return false;
        }
    }

}
