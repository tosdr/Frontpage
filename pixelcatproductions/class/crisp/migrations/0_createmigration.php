<?php

namespace crisp\migrations;

class createmigration extends \crisp\core\Migrations {

    public function run() {
        try {
            $this->begin();
            \crisp\core\Migrations::createTable("schema_migration", array("file", \crisp\core\Migrations::DB_VARCHAR));
            return $this->end();
        } catch (\Exception $ex) {
            echo $ex->getMessage() . PHP_EOL;
            $this->rollback();
            return false;
        }
    }

}
