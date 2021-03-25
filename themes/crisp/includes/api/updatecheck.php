<?php

$opts = [
    "http" => [
        "method" => "GET",
        "header" => "User-Agent: ToS;DR\r\n"
    ]
];

$context = stream_context_create($opts);

$Response = json_decode(file_get_contents("https://api.github.com/repos/tosdr/browser-extensions/releases/latest", false, $context));

if (!isset($this->Query) || empty($this->Query)) {
    echo \crisp\core\PluginAPI::response(false, "Latest GitHub Release", ["release" => $Response->tag_name]);
    exit;
} else {

    $Version = $this->Query;
    $Latest = $Response->tag_name;
    if (\crisp\api\Helper::startsWith($this->Query, "v")) {
        $Version = substr($Version, 1);
    }
    if (\crisp\api\Helper::startsWith($Latest, "v")) {
        $Latest = substr($Latest, 1);
    }

    echo \crisp\core\PluginAPI::response(false, "Comparing versions", ["latest" => $Latest, "given" => $Version, "substring" => \crisp\api\Helper::startsWith($this->Query, "v"), "compare" => version_compare($Latest, $Version)]);
}