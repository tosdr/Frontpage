<?php

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
    $request = $request->fetch(PDO::FETCH_ASSOC);

    if (!$request) {
        echo \crisp\core\PluginAPI::response(crisp\core\Bitmask::INVALID_PARAMETER, "Invalid Request", []);
        exit;
    }

    $Name = $request["name"];
    $Domains = $request["domains"];
    $Wikipedia = $request["wikipedia"];
    $Documents = json_decode($request["documents"], true);
    $service_id;
    $newstatement = $Phoenix->getDBConnector()->prepare("INSERT INTO services (name, url, wikipedia, created_at, updated_at) VALUES (:name, :url, :wikipedia, NOW(), NOW())");

    if ($newstatement->execute([":name" => $Name, ":url" => $Domains, ":wikipedia" => $Wikipedia])) {
        $service_id = $Phoenix->getDBConnector()->lastInsertId();
        foreach ($Documents as $Document) {
            $newstatementdoc = $Phoenix->getDBConnector()->prepare("INSERT INTO documents (name, url, xpath, created_at, updated_at, service_id) VALUES (:name, :url, :xpath, NOW(), NOW(), :service_id)");
            $newstatementdoc->execute([":name" => $Document["name"], ":url" => $Document["url"], ":xpath" => $Document["xpath"], ":service_id" => $service_id]);
        }

        $request = $Mysql->getDBConnector()->prepare("DELETE FROM service_requests WHERE id = :id;");
        $request->execute([":id" => $_POST["approve"]]);


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
    $request->execute([":id" => $_POST["approve"]]);


    echo \crisp\core\PluginAPI::response(crisp\core\Bitmask::REQUEST_SUCCESS, "OK", []);
    exit;
}


$requests = $Mysql->getDBConnector()->query("SELECT * FROM service_requests ORDER BY id ASC;");

$_vars = array("requests" => $requests);
