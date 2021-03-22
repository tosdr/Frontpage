<?php

namespace crisp\migrations;

class createlanguages extends \crisp\core\Migrations {

    public function run() {
        try {
            $this->begin();


            $this->createTable("Languages",
                    array("ID", $this::DB_INTEGER, "NOT NULL SERIAL"),
                    array("Name", $this::DB_VARCHAR, "NOT NULL"),
                    array("Code", $this::DB_VARCHAR, "NOT NULL"),
                    array("NativeName", $this::DB_VARCHAR, "NOT NULL"),
                    array("Flag", $this::DB_VARCHAR, "NOT NULL"),
                    array("Enabled", $this::DB_BOOL, "NOT NULL DEFAULT 0"),
            );

            $this->Database->query("INSERT INTO Languages (Name, Code, NativeName, Flag, Enabled) VALUES('base.language.en', 'en', 'base.language.native.en', 'en', 1)");



            return $this->end();
        } catch (\Exception $ex) {
            echo $ex->getMessage() . PHP_EOL;
            $this->rollback();
            return false;
        }
    }

}
