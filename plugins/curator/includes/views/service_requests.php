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

$User = new crisp\plugin\curator\PhoenixUser($_SESSION[\crisp\core\Config::$Cookie_Prefix . "session_login"]["user"]);

if (!$User->isSessionValid()) {
    header("Location: " . \crisp\api\Helper::generateLink("login/?invalid_sr"));
    exit;
}

$Mysql = new \crisp\core\MySQL();
$Phoenix = new \crisp\plugin\curator\Phoenix();

if (isset($_POST["approve"]) && !empty($_POST["approve"])) {

    $request = $Mysql->getDBConnector()->prepare("SELECT * FROM service_requests WHERE id = :id;");
    $request->execute([":id" => $_POST["approve"]]);
    $_request = $request->fetch(PDO::FETCH_ASSOC);

    if (!$_request) {
        echo \crisp\core\PluginAPI::response(crisp\core\Bitmask::INVALID_PARAMETER, "Invalid Request", []);
        exit;
    }



    $Name = $_request["name"];
    $Domains = $_request["domains"];
    $Wikipedia = $_request["wikipedia"];
    $Documents = json_decode($_request["documents"], true);
    $service_id = \crisp\api\Phoenix::createService($Name, $Domains, $Wikipedia, $User->UserID);

    if ($service_id !== false) {
        foreach ($Documents as $Document) {
            \crisp\api\Phoenix::createDocument($Document["name"], $Document["url"], $Document["xpath"], $service_id, $User->UserID);
        }

        $request = $Mysql->getDBConnector()->prepare("DELETE FROM service_requests WHERE id = :id;");
        $request->execute([":id" => $_POST["approve"]]);



        if ($_request["email"]) {
            $mail = new PHPMailer();

            $mail->IsSMTP();
            $mail->CharSet = 'UTF-8';

            $mail->setFrom($EnvFile['SMTP_FROM'], 'ToS;DR Service Requests');
            $mail->addAddress($_request["email"]);
            $mail->Host = $EnvFile["SMTP_HOST"];
            $mail->SMTPAuth = true;
            $mail->Timeout = 10;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $EnvFile["SMTP_PORT"];
            $mail->Username = $EnvFile["SMTP_USER"];
            $mail->Password = $EnvFile["SMTP_PASSWORD"];
            $mail->Subject = 'About your ToS;DR service request';
            $mail->Body = "Your Service Request over at tosdr.org has been approved. You can find the service here: https://edit.tosdr.org/services/$service_id";

            $mail->send();
        }

        echo \crisp\core\PluginAPI::response(crisp\core\Bitmask::REQUEST_SUCCESS, $service_id, []);
        exit;
    } else {
        echo \crisp\core\PluginAPI::response(crisp\core\Bitmask::GENERIC_ERROR, "SQL Error" . var_export($newstatement->errorInfo(), true), []);
        exit;
    }

    exit;
}

if (isset($_POST["reject"]) && !empty($_POST["reject"])) {

    $request = $Mysql->getDBConnector()->prepare("DELETE FROM service_requests WHERE id = :id;");
    $request->execute([":id" => $_POST["reject"]]);

    if ($_request["email"]) {
        $mail = new PHPMailer();

        $mail->IsSMTP();
        $mail->CharSet = 'UTF-8';

        $mail->setFrom($EnvFile['SMTP_FROM'], 'ToS;DR Service Requests');
        $mail->addAddress($_request["email"]);
        $mail->Host = $EnvFile["SMTP_HOST"];
        $mail->SMTPAuth = true;
        $mail->Timeout = 10;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $EnvFile["SMTP_PORT"];
        $mail->Username = $EnvFile["SMTP_USER"];
        $mail->Password = $EnvFile["SMTP_PASSWORD"];
        $mail->Subject = 'About your ToS;DR service request';
        $mail->Body = "Your Service Request over at tosdr.org has been rejected. You can resubmit the request at any time here: https://tosdr.org/new_service";

        $mail->send();
    }

    echo \crisp\core\PluginAPI::response(crisp\core\Bitmask::REQUEST_SUCCESS, "OK", []);
    exit;
}


$requests = $Mysql->getDBConnector()->query("SELECT * FROM service_requests ORDER BY id ASC;");

$_vars = array("requests" => $requests);
