<?php

namespace crisp\migrations;

class createplugins extends \crisp\core\Migrations {

    public function run() {
        try {
            $this->begin();

            $this->createTable("loadedPlugins",
                    array("Name", $this::DB_VARCHAR, "NOT NULL"),
                    array("loadedAt", $this::DB_TIMESTAMP, "NOT NULL DEFAULT CURRENT_TIMESTAMP()"),
                    array("order", $this::DB_INTEGER, "NOT NULL DEFAULT 0"),
            );

            \crisp\core\Plugins::install("core", null, __FILE__, "migration");


            return $this->end();
        } catch (\Exception $ex) {
            echo $ex->getMessage() . PHP_EOL;
            $this->rollback();
            return false;
        }
    }

}
