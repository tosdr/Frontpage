<?php

namespace crisp\migrations;

class createservicestats extends \crisp\core\Migrations {

    public function run() {
        try {
            $this->begin();
            $this->createTable("APIStats",
                    array("interface", \crisp\core\Migrations::DB_VARCHAR),
                    array("query", \crisp\core\Migrations::DB_VARCHAR),
                    array("count", \crisp\core\Migrations::DB_INTEGER)
            );
            return $this->end();
        } catch (\Exception $ex) {
            echo $ex->getMessage() . PHP_EOL;
            $this->rollback();
            return false;
        }
    }

}
