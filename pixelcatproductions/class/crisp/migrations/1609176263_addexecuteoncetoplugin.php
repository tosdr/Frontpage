<?php

namespace crisp\migrations;

class addexecuteoncetoplugin extends \crisp\core\Migrations {

    public function run() {
        try {
            $this->begin();
            $this->addColumn("Cron", array("ExecuteOnce", self::DB_BOOL, "DEFAULT 0"));
            return $this->end();
        } catch (\Exception $ex) {
            echo $ex->getMessage() . PHP_EOL;
            $this->rollback();
            return false;
        }
    }

}
