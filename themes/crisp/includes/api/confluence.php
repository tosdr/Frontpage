<?php

if (crisp\api\Helper::getRealIpAddr() !== "202.61.251.191") {
    echo \crisp\core\PluginAPI::response(crisp\core\Bitmask::INVALID_SUBNET, "IP not whitelisted", [], null, 401);
    exit;
}

$data = json_decode(file_get_contents('php://input'));

if (!$data) {
    echo \crisp\core\PluginAPI::response(crisp\core\Bitmask::INVALID_PARAMETER, "Missing data", [], null, 400);
    exit;
}


if ($data->test) {
    echo \crisp\core\PluginAPI::response(crisp\core\Bitmask::REQUEST_SUCCESS, "OK", [], null);
    exit;
}

if (!$data->event == "page_updated" || $data->event == "page_created") {
    echo \crisp\core\PluginAPI::response(crisp\core\Bitmask::INVALID_PARAMETER, "Invalid Event", [], null, 400);
    exit;
}

$confluencePage = json_decode(file_get_contents("https://docs.tosdr.org/rest/api/content/" . $data->page->id));
#$confluencePage = json_decode(file_get_contents("https://docs.tosdr.org/rest/api/content/360496"));



/* Test Webhooks */


$curltest = curl_init();

curl_setopt_array($curltest, array(
    CURLOPT_URL => "https://webhook.site/d4031044-a254-400e-843e-5e16c1c957b4",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS => json_encode($confluencePage),
    CURLOPT_HTTPHEADER => array(
        'Content-Type: application/json'
    ),
));

curl_exec($curltest);

curl_close($curltest);


/* End Test */

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

$curl = curl_init();
$EnvFile = parse_ini_file(__DIR__ . "/../../../../.env");

if (!$EnvFile["CONFLUENCE_DISCORD_WEBHOOK"]) {
    echo \crisp\core\PluginAPI::response(crisp\core\Bitmask::GENERIC_ERROR, "Webhook not set", [], null, 500);
    exit;
}

curl_setopt_array($curl, array(
    CURLOPT_URL => $EnvFile["CONFLUENCE_DISCORD_WEBHOOK"],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS => json_encode($embed),
    CURLOPT_HTTPHEADER => array(
        'Content-Type: application/json'
    ),
));

$resp = curl_exec($curl);

if (!$resp) {
    echo \crisp\core\PluginAPI::response(crisp\core\Bitmask::GENERIC_ERROR, curl_errno($curl), [], null, 502);
}

curl_close($curl);
