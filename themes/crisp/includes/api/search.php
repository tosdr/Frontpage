<?php

$Interface = "default";

if (is_array($GLOBALS["route"]->GET)) {
    $Interface = array_key_first($GLOBALS["route"]->GET);

    $this->Query = $GLOBALS["route"]->GET[$Interface];
    if (strpos($GLOBALS["route"]->GET[$Interface], ".json")) {
        $this->Query = substr($this->Query, 0, -5);
    }
}

switch ($Interface) {
    case "v2":
        require_once __DIR__ . '/search/v2.php';
    case "v1":
    default:
        require_once __DIR__ . '/search/v1.php';
}