<?php

namespace crisp\migrations;

class createsessiontable extends \crisp\core\Migrations {

    public function run() {
        try {
            $this->begin();
            $this->createTable("Sessions",
                    array("ID", $this::DB_INTEGER, "NOT NULL AUTO_INCREMENT"),
                    array("Token", $this::DB_VARCHAR, "NOT NULL"),
                    array("User", $this::DB_INTEGER, "NOT NULL"),
                    array("CreatedAt", $this::DB_TIMESTAMP, "NOT NULL DEFAULT CURRENT_TIMESTAMP()"),
                    array("Identifier", $this::DB_VARCHAR, "NOT NULL DEFAULT 'login'")
            );
            $this->addIndex("Sessions", "Token", $this::DB_PRIMARYKEY);
            return $this->end();
        } catch (\Exception $ex) {
            echo $ex->getMessage() . PHP_EOL;
            $this->rollback();
            return false;
        }
    }

}
