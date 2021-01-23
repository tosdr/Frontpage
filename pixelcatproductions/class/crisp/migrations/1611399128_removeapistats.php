<?php

namespace crisp\migrations;

class removeapistats extends \crisp\core\Migrations {

  public function run() {
    try {
      $this->begin();
      $this->Database->query("DROP Table APIStats;");
      return $this->end();
    } catch (\Exception $ex) {
      echo $ex->getMessage() . PHP_EOL;
      $this->rollback();
      return false;
    }
  }

}
