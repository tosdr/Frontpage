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
 * Hook Class
 *
 * @author Justin René Back <jback@pixelcatproductions.net>
 */
trait Hook {

    public function __construct() {
        if (!isset($GLOBALS['hook']) && !is_array($GLOBALS['hook'])) {
            return;
        }
    }

    /**
     * Listen on a specific hook and wait for it's message
     * @param string $channel The hook to listen on
     * @param function|string $func The function to send the response to
     */
    public static function on($channel, $func) {
        if (!isset($GLOBALS['hook'][$channel])) {

            $GLOBALS['hook'][$channel] = array();
            //$GLOBALS['hook'][$channel]["parameters"] = null;
        }

        array_push($GLOBALS['hook'][$channel], $func);
    }

    /**
     * 
     * @param string $channel The channel to broadcast too
     * @param any ...$parameters Parameters to attach to the broadcast
     */
    private function broadcastHook($channel, ...$parameters) {
        if (isset($GLOBALS['hook'][$channel])) {
            foreach ($GLOBALS['hook'][$channel] as $func) {
                $GLOBALS['hook'][$channel]["parameters"] = $parameters;
                call_user_func($func, $parameters);
            }
            return count($GLOBALS['hook'][$channel]);
        }
    }

}
