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

if (!$userDetails["admin"]) {
    header("Location: /dashboard");
    exit;
}

$Mysql = new \crisp\core\MySQL();
$Phoenix = new \crisp\plugin\curator\Phoenix();

function lookupUser($Input) {
    if (filter_var($Input, FILTER_VALIDATE_EMAIL)) {
        $UserDetails = crisp\plugin\curator\PhoenixUser::fetchStaticByEmail($Input);

        if (!$UserDetails) {
            return false;
        }
        return array("email" => $UserDetails["email"], "id" => $UserDetails["id"], "username" => $UserDetails["username"]);
    } elseif (filter_var($Input, FILTER_VALIDATE_INT)) {
        $UserDetails = crisp\plugin\curator\PhoenixUser::fetchStatic($Input);

        if (!$UserDetails) {
            return false;
        }
        return array("email" => $UserDetails["email"], "id" => $UserDetails["id"], "username" => $UserDetails["username"]);
    } else {

        $UserDetails = crisp\plugin\curator\PhoenixUser::fetchStaticByUsername($Input);

        if (!$UserDetails) {
            return false;
        }
        return array("email" => $UserDetails["email"], "id" => $UserDetails["id"], "username" => $UserDetails["username"]);
    }
}

if (isset($_POST["verify"]) && !empty($_POST["verify"])) {

    $userDetails = lookupUser($_POST["verify"]);

    if (!$userDetails) {
        echo \crisp\core\PluginAPI::response(crisp\core\Bitmask::INVALID_PARAMETER, "User not found", []);
        exit;
    }
    echo \crisp\core\PluginAPI::response(crisp\core\Bitmask::REQUEST_SUCCESS, "OK", $userDetails);
    exit;
} elseif (isset($_POST["create"]) && !empty($_POST["create"])) {
    $token = bin2hex(openssl_random_pseudo_bytes(32));
    $permissions = (int) $_POST["permissions"] ?? 1;
    $user = lookupUser($_POST["user"]) ?? null;
    $ratelimit_second = (int) $_POST["ratelimit_second"] ?? null;
    $ratelimit_hour = (int) $_POST["ratelimit_hour"] ?? null;
    $ratelimit_day = (int) $_POST["ratelimit_day"] ?? null;
    $benefit = $_POST["benefit"] ?? null;
    $expires_at = $_POST["expires_at"] ?? null;
    $type = $_POST["type"] ?? "production";


    if (!$user) {
        echo \crisp\core\PluginAPI::response(crisp\core\Bitmask::INVALID_PARAMETER, "User not found", []);
        exit;
    }


    if ($ratelimit_second === 0) {
        $ratelimit_second = null;
    }
    if ($ratelimit_hour === 0) {
        $ratelimit_hour = null;
    }
    if ($ratelimit_day === 0) {
        $ratelimit_day = null;
    }

    if ($expires_at !== null) {
        $expires_at = date('Y-m-d H:i:s', strtotime($expires_at));
    }

    if ($type === "development") {
        $expires_at = date('Y-m-d H:i:s', strtotime("+30 days"));
    }


    $request = $Mysql->getDBConnector()->prepare("INSERT INTO apikeys (key, userid, ratelimit_second, ratelimit_hour, ratelimit_day, ratelimit_benefit, expires_at, permissions) VALUES (:key, :userid, :second, :hour, :day, :benefit, :expires, :permissions)");
    $added = $request->execute([
        ":key" => $token,
        ":userid" => $user["id"],
        ":second" => $ratelimit_second,
        ":hour" => $ratelimit_hour,
        ":day" => $ratelimit_day,
        ":benefit" => $benefit,
        ":expires" => $expires_at,
        ":permissions" => $permissions
    ]);

    if (!$added) {
        echo \crisp\core\PluginAPI::response(crisp\core\Bitmask::GENERATE_FAILED, "Failed to generate key, server error", []);
        exit;
    }

    $mail = new PHPMailer();

    $mail->IsSMTP();
    $mail->CharSet = 'UTF-8';

    $PermissionsPretty;

    foreach (\crisp\core\APIPermissions::getConstants() as $Key => $Permission) {
        if ($Permission & $permissions) {
            $PermissionsPretty[] = $Key;
        }
    }

    $mail->setFrom($EnvFile['SMTP_FROM'], 'ToS;DR Developers');
    $mail->addAddress($user["email"]);
    $mail->Host = $EnvFile["SMTP_HOST"];
    $mail->SMTPAuth = true;
    $mail->Timeout = 10;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = $EnvFile["SMTP_PORT"];
    $mail->Username = $EnvFile["SMTP_USER"];
    $mail->isHTML(true);
    $mail->Password = $EnvFile["SMTP_PASSWORD"];
    $mail->Subject = 'A ToS;DR API Key has been generated.';
    $mail->Body = "Hello $user[username]!<br><br>This is a quick heads up that we have generated a ToS;DR API Key for you! <br><br>API Key: <b>$token</b><br><br>Permissions: " . implode(",", $PermissionsPretty) . "<br>Expires at: " . ($expires_at ?? "Never") . "<br>Ratelimit s/h/d: " . ($ratelimit_second ?? "15") . "/" . ($ratelimit_hour ?? "1000") . "/" . ($ratelimit_day ?? "15000") . "<br>Benefit: " . ($benefit ?? "None") . "<br><br>For more info regarding this you can contact team@tosdr.org.";

    $mail->send();

    echo \crisp\core\PluginAPI::response(crisp\core\Bitmask::REQUEST_SUCCESS, "OK", array(
        "token" => $token,
        "permissions" => $permissions,
        "user" => $user["id"],
        "mail" => $mail->Body,
        "rl_second" => $ratelimit_second,
        "rl_hour" => $ratelimit_hour,
        "rl_day" => $ratelimit_day,
        "benefit" => $benefit,
        "expires_at" => $expires_at,
        "type" => $type
    ));
    exit;


    exit;
} elseif (isset($_POST["revoke"]) && !empty($_POST["revoke"])) {
    $request = $Mysql->getDBConnector()->prepare("SELECT * FROM apikeys WHERE key = :id;");
    $request->execute([":id" => $_POST["revoke"]]);
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
            $mail->Body = "Hello $UserDetails[username]!<br><br>This is a quick heads up that we have revoked your ToS;DR API Key starting with <b>$shortenedAPIKey...</b>.<br> For more info regarding this you can contact team@tosdr.org.";

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
    $_totalKeys = $Mysql->getDBConnector()->query("SELECT COUNT(key) As amount FROM apikeys;")->fetch(\PDO::FETCH_ASSOC)["amount"];
} else {
    $statement = $Mysql->getDBConnector()->prepare("SELECT COUNT(key) As amount FROM apikeys WHERE key LIKE CONCAT('%', :query, '%') OR ratelimit_benefit LIKE CONCAT('%', :query, '%');");
    $statement->execute([":query" => $GLOBALS["route"]->GET["query"]]);
    $_totalKeys = $statement->fetch(\PDO::FETCH_ASSOC)["amount"];
}
$_pages = ceil($_totalKeys / $_limit);

$_offset = ($_pages - 1) * $_limit;




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
    $_offsetQuery = $Mysql->getDBConnector()->prepare("SELECT * FROM apikeys ORDER BY created_at DESC LIMIT :limit OFFSET :offset");

    $_offsetQuery->execute([":limit" => $_limit, ":offset" => $_offset]);
} else {
    $_offsetQuery = $Mysql->getDBConnector()->prepare("SELECT * FROM apikeys WHERE key LIKE CONCAT('%', :query, '%') OR ratelimit_benefit LIKE CONCAT('%', :query, '%') ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
    $_offsetQuery->execute([":limit" => $_limit, ":offset" => $_offset, ":query" => $GLOBALS["route"]->GET["query"]]);
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
