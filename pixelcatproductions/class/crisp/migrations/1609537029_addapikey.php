<?php

namespace crisp\migrations;

class addapikey extends \crisp\core\Migrations {

    public function run() {
        try {
            $this->begin();
            $this->createTable("APIKeys",
                    array("key", $this::DB_VARCHAR),
                    array("UserID", $this::DB_INTEGER),
                    array("last_changed", $this::DB_TIMESTAMP, "NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP()"),
                    array("revoked", $this::DB_INTEGER, "NOT NULL DEFAULT 0"),
                    array("created_at", $this::DB_TIMESTAMP, "NOT NULL DEFAULT CURRENT_TIMESTAMP()")
            );
            $this->addIndex("APIKeys", "key", $this::DB_UNIQUEKEY, "apikey");
            return $this->end();
        } catch (\Exception $ex) {
            echo $ex->getMessage() . PHP_EOL;
            $this->rollback();
            return false;
        }
    }

}
