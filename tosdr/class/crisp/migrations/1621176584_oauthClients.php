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


namespace crisp\migrations;

if(!defined('CRISP_COMPONENT')){
    echo 'Cannot access this component directly!';
    exit;
}

class OAuthClients extends \crisp\core\Migrations {

    public function run() {
        try {
            $this->begin();
            $this->createTable("oauth_clients",
                array("client_id", \crisp\core\Migrations::DB_VARCHAR, "NOT NULL"),
                array("client_secret", \crisp\core\Migrations::DB_VARCHAR, "NOT NULL"),
                array("redirect_uri", \crisp\core\Migrations::DB_TEXT),
                array("grant_types", \crisp\core\Migrations::DB_VARCHAR),
                array("scope", \crisp\core\Migrations::DB_BIGINT),
                array("user_id", \crisp\core\Migrations::DB_BIGINT)
            );
            $this->addIndex("oauth_clients", "client_id", $this::DB_PRIMARYKEY);
            return $this->end();
        } catch (\Exception $ex) {
            echo $ex->getMessage() . PHP_EOL;
            $this->rollback();
            return false;
        }
    }

}
