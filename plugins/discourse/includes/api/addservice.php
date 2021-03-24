<?php

/** @var \crisp\core\PluginAPI $this */
/** @var \crisp\core\PluginAPI self */
$EnvFile = parse_ini_file(__DIR__ . "/../../.env");
$Error = false;
$Message = "";


if (empty($EnvFile["DISCOURSE_WEBHOOK_SECRET"])) {
    $Error[] = "DISCOURSE_WEBHOOK_SECRET is not set in .env file";
}


if ($_GET["q"] == $EnvFile["DISCOURSE_WEBHOOK_SECRET"]) {
    $JSON = json_decode(file_get_contents('php://input'), true);
    $Service = \crisp\api\Phoenix::getServiceByNamePG($JSON["service_name"]);

    if (!$Service) {
        $Error[] = "DUPLICATE";
        file_get_contents("https://webhook.site/079dfdd7-2620-46fa-ab03-9ab3c74b1110?duplicate");
    } elseif ($JSON !== null) {


        $ServiceName = $JSON["service_name"];
        //$Documents = explode("\n", $JSON["documents"]);
        $Domains = $JSON["domains"];
        $Wikipedia = $JSON["wikipedia"];

        $Postgres = new \crisp\core\Postgres();

        /** @var \PDO $Database */
        $Database = $Postgres->getDBConnector();

        if (strpos("http://", $Database) !== false || strpos("https://", $Database) !== false) {
            $Error[] = "INVALID_DOMAIN";
            file_get_contents("https://webhook.site/079dfdd7-2620-46fa-ab03-9ab3c74b1110?invalid_domain");
        } else {
            $Database->beginTransaction();


            $statement = $Database->prepare("INSERT INTO services (name, url, created_at, updated_at, wikipedia, user_id) VALUES (:name, :url, NOW(), NOW(), :wikipedia, 21311)");
            if ($statement->execute(array(":name" => $ServiceName, ":url" => $Domains, ":wikipedia" => $Wikipedia))) {
                $Database->commit();
                file_get_contents("https://webhook.site/079dfdd7-2620-46fa-ab03-9ab3c74b1110?committed");
            } else {
                $Database->rollBack();
                file_get_contents("https://webhook.site/079dfdd7-2620-46fa-ab03-9ab3c74b1110?inserterror");
            }
        }
    }
}


$this->response($Error, $Message, []);
