<?php

/*
 * Copyright 2020 Pixelcat Productions <support@pixelcatproductions.net>
 * @author 2020 Justin René Back <jback@pixelcatproductions.net>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace crisp\core;

/**
 * Interact with the database yourself. Please use this interface only when you REALLY need it for custom tables.
 * We offer a variety of functions to interact with users or the system itself in a safe way :-)
 */
class Redis {

    private $Database_Connection;

    /**
     * Constructs the Database_Connectio
     * @see getDBConnector
     */
    public function __construct() {
        try {
            $EnvFile = parse_ini_file(__DIR__ . "/../../../../.env");
            $redis = new \Redis();
            $redis->connect($EnvFile["REDIS_HOST"], $EnvFile["REDIS_PORT"]);
            $redis->auth($EnvFile["REDIS_AUTH"]);
            $this->Database_Connection = $redis;
        } catch (\Exception $ex) {
            throw new \Exception("Failed to contact redis server");
        }
    }

    /**
     * Get the database connector
     * @return \Redis
     */
    public function getDBConnector() {
        return $this->Database_Connection;
    }

}
