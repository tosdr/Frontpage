<?php

namespace crisp\migrations;

class addnotetoservice extends \crisp\core\Migrations {

    public function run() {
        try {
            $this->begin();
            $this->addColumn("service_requests", array("note", self::DB_TEXT, "DEFAULT NULL"));
            return $this->end();
        } catch (\Exception $ex) {
            echo $ex->getMessage() . PHP_EOL;
            $this->rollback();
            return false;
        }
    }

}
