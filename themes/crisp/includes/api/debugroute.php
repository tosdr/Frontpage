<?php

echo '<pre>' . var_export($_GET, true) . '</pre>';

echo '<pre>' . var_export($GLOBALS["route"], true) . '</pre>';

echo '<pre>' . var_export($_GET["serviceid"] ?? $GLOBALS["route"]->GET["q"], true) . '</pre>';