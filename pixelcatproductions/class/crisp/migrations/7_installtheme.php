<?php

namespace crisp\migrations;

class installtheme extends \crisp\core\Migrations {

    public function run() {
        try {
            $this->begin();
            \crisp\core\Themes::install("crisp");
            return $this->end();
        } catch (\Exception $ex) {
            echo $ex->getMessage() . PHP_EOL;
            $this->rollback();
            return false;
        }
    }

}
