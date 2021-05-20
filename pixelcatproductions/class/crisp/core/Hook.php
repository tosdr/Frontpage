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

/**
 * Hook Class
 *
 * @author Justin René Back <jback@pixelcatproductions.net>
 */
trait Hook {

    /**
     * Listen on a specific hook and wait for it's message
     * @param string $channel The hook to listen on
     * @param mixed $func The function to send the response to
     */
    public static function on(string $channel, mixed $func): void
    {
        if (!isset($GLOBALS['hook'][$channel])) {

            $GLOBALS['hook'][$channel] = [];
        }

        $GLOBALS['hook'][$channel][] = $func;
    }

    /**
     *
     * @param string $channel The channel to broadcast too
     * @param mixed ...$parameters Parameters to attach to the broadcast
     * @return int
     */
    public static function broadcastHook(string $channel, ...$parameters): int
    {
        if (isset($GLOBALS['hook'][$channel])) {
            foreach ($GLOBALS['hook'][$channel] as $func) {
                $GLOBALS['hook'][$channel]['parameters'] = $parameters;
                $func($parameters);
            }
            return count($GLOBALS['hook'][$channel]);
        }
        return 0;
    }

}
