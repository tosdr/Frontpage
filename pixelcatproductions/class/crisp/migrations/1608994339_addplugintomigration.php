<?php

namespace crisp\migrations;

class addplugintomigration extends \crisp\core\Migrations {

    public function run() {
        try {
            $this->begin();

            if (!$this->isMigrated("0_createmigration")) {
                $this->addColumn("schema_migration", array("plugin", self::DB_VARCHAR, "DEFAULT NULL"));
            }
            return $this->end();
        } catch (\Exception $ex) {
            echo $ex->getMessage() . PHP_EOL;
            $this->rollback();
            return false;
        }
    }

}
