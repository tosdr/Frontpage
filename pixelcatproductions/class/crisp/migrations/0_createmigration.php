<?php

namespace crisp\migrations;

class createmigration extends \crisp\core\Migrations {

  public function run() {
    try {
      $this->begin();
      \crisp\core\Migrations::createTable("schema_migration", array("file", \crisp\core\Migrations::DB_VARCHAR));

      if (!$this->isMigrated("1608994339_addplugintomigration")) {
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
