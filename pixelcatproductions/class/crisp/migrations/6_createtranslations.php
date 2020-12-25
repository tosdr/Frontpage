<?php

namespace crisp\migrations;

class createtranslations extends \crisp\core\Migrations {

    public function run() {
        try {
            $this->begin();
            $this->createTable("Translations",
                    array("key", $this::DB_VARCHAR),
                    array("en", $this::DB_TEXT),
            );
            $this->addIndex("Translations", "key", $this::DB_PRIMARYKEY);
            return $this->end();
        } catch (\Exception $ex) {
            echo $ex->getMessage() . PHP_EOL;
            $this->rollback();
            return false;
        }
    }

}
