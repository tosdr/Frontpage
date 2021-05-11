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

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$EnvFile = parse_ini_file(__DIR__ . "/../../../../.env");
include __DIR__ . '/../Phoenix.php';
header("X-SKIPCACHE: 1");

if (!isset($_SESSION[\crisp\core\Config::$Cookie_Prefix . "session_login"])) {
    header("Location: " . \crisp\api\Helper::generateLink("login/?invalid_sess_sr"));
    exit;
}

if (!$User->isSessionValid()) {
    header("Location: " . \crisp\api\Helper::generateLink("login/?invalid_sr"));
    exit;
}

$Mysql = new \crisp\core\MySQL();
$Phoenix = new \crisp\plugin\curator\Phoenix();


if (isset($_POST["revoke"]) && !empty($_POST["revoke"])) {
    $request = $Mysql->getDBConnector()->prepare("SELECT * FROM apikeys WHERE key = :id AND userid = :uid;");
    $request->execute([":id" => $_POST["revoke"], ":uid" => $User->UserID]);
    $_request = $request->fetch(PDO::FETCH_ASSOC);

    if (!$_request) {
        echo \crisp\core\PluginAPI::response(crisp\core\Bitmask::INVALID_PARAMETER, "Invalid API Key", []);
        exit;
    }

    if ($_request["revoked"]) {
        echo \crisp\core\PluginAPI::response(crisp\core\Bitmask::INVALID_PARAMETER, "API Key is already revoked", []);
        exit;
    }


    $delrequest = $Mysql->getDBConnector()->prepare("UPDATE apikeys SET revoked = 1, last_changed = NOW() WHERE key = :id;");
    if (!$delrequest->execute([":id" => $_POST["revoke"]])) {
        echo \crisp\core\PluginAPI::response(crisp\core\Bitmask::GENERIC_ERROR, "Failed to revoke", []);
        exit;
    }


    if ($_request["userid"]) {

        $UserDetails = crisp\plugin\curator\PhoenixUser::fetchStatic($_request["userid"]);


        if ($UserDetails) {


            $shortenedAPIKey = substr($_request["key"], 0, 6);

            $mail = new PHPMailer();

            $mail->IsSMTP();
            $mail->CharSet = 'UTF-8';

            $mail->setFrom($EnvFile['SMTP_FROM'], 'ToS;DR Developers');
            $mail->addAddress($UserDetails["email"]);
            $mail->Host = $EnvFile["SMTP_HOST"];
            $mail->SMTPAuth = true;
            $mail->Timeout = 10;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $EnvFile["SMTP_PORT"];
            $mail->Username = $EnvFile["SMTP_USER"];
            $mail->isHTML(true);
            $mail->Password = $EnvFile["SMTP_PASSWORD"];
            $mail->Subject = 'Your ToS;DR API Key has been revoked.';
            $mail->Body = "Hello $UserDetails[username]!<br><br>This is a quick heads up that we have revoked your ToS;DR API Key starting with <b>$shortenedAPIKey...</b>.<br> For more info regarding this you can contact team@tosdr.org with your SEN $_request[sen]";

            $mail->send();
        }
    }

    echo \crisp\core\PluginAPI::response(crisp\core\Bitmask::REQUEST_SUCCESS, $_request["key"], []);

    exit;
} elseif ($_SERVER["REQUEST_METHOD"] === "POST") {
    echo \crisp\core\PluginAPI::response(crisp\core\Bitmask::NOT_IMPLEMENTED, "Not implemented", []);
    exit;
}

$_totalKeys;
$_limit = 15;
if (!isset($GLOBALS["route"]->GET["query"]) && empty($GLOBALS["route"]->GET["query"])) {
    $_totalKeys = $Mysql->getDBConnector()->prepare("SELECT COUNT(key) As amount FROM apikeys WHERE userid = :uid");

    $_totalKeys->execute([":uid" => $User->UserID]);

    $_totalKeys = $_totalKeys->fetch(\PDO::FETCH_ASSOC)["amount"];

} else {
    $statement = $Mysql->getDBConnector()->prepare("SELECT COUNT(key) As amount FROM apikeys WHERE userid = :uid AND (key LIKE CONCAT('%', :query, '%') OR ratelimit_benefit LIKE CONCAT('%', :query, '%') OR sen LIKE CONCAT('%', :query, '%'));");
    $statement->execute([":uid" => $User->UserID, ":query" => $GLOBALS["route"]->GET["query"]]);
    $_totalKeys = $statement->fetch(\PDO::FETCH_ASSOC)["amount"];
}
$_pages = ceil($_totalKeys / $_limit);

$_offset = ($_pages - 1) * $_limit;

if($_offset < 0){
    $_offset = 0;
}


$_page = min($_pages, filter_var($GLOBALS["route"]->GET["page"], FILTER_VALIDATE_INT, array(
    'options' => array(
        'default' => 1,
        'min_range' => 1
        ))));


if ($_page === 1) {
    $_offset = 0;
}



$_start = $_offset + 1;
$_end = min(($_offset + $_limit), $_totalKeys);

$_offsetQuery;

if (!isset($GLOBALS["route"]->GET["query"]) && empty($GLOBALS["route"]->GET["query"])) {
    $_offsetQuery = $Mysql->getDBConnector()->prepare("SELECT * FROM apikeys WHERE userid = :uid ORDER BY created_at DESC LIMIT :limit OFFSET :offset");

    $_offsetQuery->execute([":limit" => $_limit, ":offset" => $_offset, ":uid" => $User->UserID]);
} else {
    $_offsetQuery = $Mysql->getDBConnector()->prepare("SELECT * FROM apikeys WHERE userid = :uid AND (key LIKE CONCAT('%', :query, '%') OR ratelimit_benefit LIKE CONCAT('%', :query, '%') OR sen LIKE CONCAT('%', :query, '%')) ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
    $_offsetQuery->execute([":uid" => $User->UserID ,":limit" => $_limit, ":offset" => $_offset, ":query" => $GLOBALS["route"]->GET["query"]]);
}
$_vars = array(
    "keys" => $_offsetQuery->fetchAll(\PDO::FETCH_ASSOC),
    "pages" => $_pages,
    "nextPagination" => $_page + 1,
    "previousPagination" => $_page - 1,
    "currentPagination" => $_page,
    "firstPagination" => $_start,
    "lastPagination" => $_pages,
    "bitmasks" => crisp\core\APIPermissions::getConstants()
);
