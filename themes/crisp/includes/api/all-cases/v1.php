<?php

$Cases = \crisp\api\Phoenix::getCasesPG();

echo \crisp\core\PluginAPI::response(crisp\core\Bitmask::REQUEST_SUCCESS, "All cases below", $Cases);
