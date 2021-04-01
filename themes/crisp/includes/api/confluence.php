<?php

if (crisp\api\Helper::getRealIpAddr() !== "202.61.251.191") {
    echo \crisp\core\PluginAPI::response(crisp\core\Bitmask::INVALID_SUBNET, "IP not whitelisted", []);
    exit;
}

$data = json_decode(file_get_contents('php://input'));

if (!$data) {
    echo \crisp\core\PluginAPI::response(crisp\core\Bitmask::INVALID_PARAMETER, "Missing data", []);
    exit;
}

if (!$data->event == "page_updated" || $data->event == "page_created") {
    echo \crisp\core\PluginAPI::response(crisp\core\Bitmask::INVALID_PARAMETER, "Invalid Event", []);
    exit;
}

$confluencePage = json_decode(file_get_contents("https://docs.tosdr.org/rest/api/content/" . $data->page->id));
#$confluencePage = json_decode(file_get_contents("https://docs.tosdr.org/rest/api/content/360496"));

$embed = array(
    'content' => 'The docs have been updated',
    'embeds' =>
    array(
        array(
            'title' => $confluencePage->title,
            'description' => $confluencePage->version->by->displayName . ' has updated the ' . $confluencePage->space->name . ' docs.',
            'url' => 'https://docs.tosdr.org/pages/viewpage.action?pageId=' . $confluencePage->id,
            'color' => 0,
            'footer' =>
            array(
                'text' => 'Confluence',
            ),
        ),
    ),
);

curl_init();
$EnvFile = parse_ini_file(__DIR__ . "/../../../../.env");


curl_setopt_array($curl, array(
    CURLOPT_URL => $EnvFile["CONFLUENCE_DISCORD_WEBHOOK"],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS => json_encode($embed),
    CURLOPT_HTTPHEADER => array(
        'Content-Type: application/json',
        'Cookie: __cfduid=da10b465cbb61e008d647271bb47b4cc21615412202'
    ),
));

curl_exec($curl);

curl_close($curl);
