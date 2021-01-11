<?php

namespace crisp\migrations;

class installtheme extends \crisp\core\Migrations {

  public function run() {
    try {
      $this->begin();
      $this->Database->query("INSERT INTO Config (`key`, value) VALUES ('theme_dir', 'themes')");
      $this->Database->query("INSERT INTO Config (`key`, value) VALUES ('plugin_dir', 'plugins')");
      \crisp\core\Themes::install("crisp");
      return $this->end();
    } catch (\Exception $ex) {
      echo $ex->getMessage() . PHP_EOL;
      $this->rollback();
      return false;
    }
  }

}
