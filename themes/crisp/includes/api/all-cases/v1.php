<?php

$Cases = \crisp\api\Phoenix::getCasesPG((array_key_first($GLOBALS["route"]->GET) == "nocache" ? true : false));

echo \crisp\core\PluginAPI::response(crisp\core\Bitmask::REQUEST_SUCCESS, "All cases below", $Cases);
