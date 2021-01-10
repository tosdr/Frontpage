<?php

use PHPUnit\Framework\TestCase;

final class BootstrapTest extends TestCase {

  public function testInit(): void {
    $this->assertEquals(true, \crisp\api\Config::create("plugin_heroku_database_uri", "postgres://postgres:postgres@postgres:5432/phoenix_development"));
  }

  public function testDatabase(): void {
    $DB = new crisp\core\Postgres();


    $DB->getDBConnector();
  }

}
