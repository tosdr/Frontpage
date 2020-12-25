<?php

namespace crisp\migrations;

class MigrationName extends \crisp\core\Migrations {

    public function run() {
        try {
            $this->begin();
            RUNCODE;
            return $this->end();
        } catch (\Exception $ex) {
            echo $ex->getMessage() . PHP_EOL;
            $this->rollback();
            return false;
        }
    }

}
