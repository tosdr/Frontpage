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

namespace crisp\core;

use OAuth2;

class OAuth
{

    public static function createServer(): OAuth2\Server
    {
        $EnvFile = parse_ini_file(__DIR__ . "/../../../../.env");
        $storage = new OAuth2\Storage\Pdo(array(
            'dsn' => "pgsql:host=$EnvFile[MYSQL_HOSTNAME];dbname=$EnvFile[MYSQL_DATABASE]",
            'username' => $EnvFile["MYSQL_USERNAME"],
            'password' => $EnvFile["MYSQL_PASSWORD"])
        );

        $server = new OAuth2\Server($storage);

        $server->addGrantType(new OAuth2\GrantType\AuthorizationCode($storage));

        return $server;
    }

}
