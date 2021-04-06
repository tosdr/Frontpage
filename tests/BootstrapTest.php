<?php

/* 
 * Copyright (C) 2021 Justin René Back <justin@tosdr.org>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

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
