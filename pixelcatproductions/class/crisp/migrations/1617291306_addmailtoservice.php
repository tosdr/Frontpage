<?php

namespace crisp\migrations;

class addmailtoservice extends \crisp\core\Migrations {

    public function run() {
        try {
            $this->begin();
            $this->addColumn("service_requests", array("email", self::DB_VARCHAR, "DEFAULT NULL"));
            return $this->end();
        } catch (\Exception $ex) {
            echo $ex->getMessage() . PHP_EOL;
            $this->rollback();
            return false;
        }
    }

}
