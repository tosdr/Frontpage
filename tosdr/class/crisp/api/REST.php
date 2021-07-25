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


namespace crisp\api;


use crisp\core\Bitmask;
use JsonException;

/**
 * Used internally, plugin loader
 *
 */
class REST
{


    /**
     * Send a JSON response
     * @param int $Status
     * @param string|null $message A message to send
     * @param array $Parameters Some response parameters
     * @param int $HTTP
     * @param mixed $Flags JSON_ENCODE constants
     * @throws JsonException
     */
    public static function response(int $Status = Bitmask::NONE, ?string $message = null, array $Parameters = [], int $HTTP = 200, mixed $Flags = null): void
    {
        header('Content-Type: application/json');
        http_response_code($HTTP);
        echo json_encode([
            'status' => $Status,
            'message' => $message,
            'parameters' => $Parameters,
            'request_id' => REQUEST_ID
        ], JSON_THROW_ON_ERROR | $Flags);
    }

}
