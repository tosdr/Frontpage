<?php

/*
 * Copyright 2020 Pixelcat Productions <support@pixelcatproductions.net>
 * @author 2020 Justin René Back <jback@pixelcatproductions.net>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

if (php_sapi_name() === 'cli' && !defined("CRISP_CLI")) {
    define('CRISP_CLI', true);
    define('CRISP_API', true);
    define('NO_KMS', true);
    error_reporting(error_reporting() & ~E_NOTICE);
    require_once __DIR__ . "/../../pixelcatproductions/crisp.php";
    include __DIR__ . '/includes/User.php';
    include __DIR__ . '/includes/Users.php';
    switch ($argv[1]) {
        case "exportusers":

            $Mysql = new \crisp\core\MySQL();

            $DB = $Mysql->getDBConnector();


            break;
        case "create":
            $User = \crisp\plugin\admin\Users::create();

            $User->enableTransaction();

            $User->setEmail($argv[2]);
            $User->setFirstname($argv[3]);
            $User->setLastname($argv[4]);
            $User->setPassword($argv[5]);
            $User->setLevel($argv[6]);
            $Success = $User->commitTransaction();


            if ($Success) {
                echo "Created new user!" . PHP_EOL;
                exit;
            } else {
                echo "Failed to create user!" . PHP_EOL;
                exit;
            }

            break;
        case "delete":
            if ($argv[2] == "system") {
                throw new \Exception("Cannot delete");
            }
            $User = \crisp\plugin\admin\Users::fetchByEmail($argv[2]);

            var_dump($User->attemptDelete()) . PHP_EOL;
            break;
        default:
            echo "php hook.php create EMAIL FIRSTNAME LASTNAME PASSWORD ACCESS_LEVEL" . PHP_EOL . "php hook.php delete EMAIL" . PHP_EOL;
    }

    exit;
}

/** @var crisp\core\Plugin $this */
$this->registerInstallHook(function($Callback) {
    echo "Creating SQL Tables" . PHP_EOL;
    $SQL = "CREATE TABLE `Users` (
  `ID` INT(11) NOT NULL AUTO_INCREMENT,
  `Email` VARCHAR(255) DEFAULT NULL,
  `Firstname` VARCHAR(255) DEFAULT NULL,
  `Lastname` VARCHAR(255) DEFAULT NULL,
  `Password` LONGBLOB DEFAULT NULL,
  `Level` INT(11) NOT NULL DEFAULT 0,
  `Activated` TINYINT(1) NOT NULL DEFAULT 0,
  `CreatedAt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  `UpdatedAt` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP(),
  KEY `ID` (`ID`)
) ENGINE=INNODB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;
CREATE TABLE `Sessions` (
  `ID` INT(11) NOT NULL AUTO_INCREMENT,
  `Token` VARCHAR(255) NOT NULL,
  `User` INT(11) NOT NULL,
  `CreatedAt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  `Identifier` VARCHAR(255) NOT NULL DEFAULT 'login',
  PRIMARY KEY (`Token`),
  KEY `ID` (`ID`),
  KEY `fk_sessions_users` (`User`),
  CONSTRAINT `fk_sessions_users` FOREIGN KEY (`User`) REFERENCES `Users` (`ID`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=INNODB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;
INSERT INTO `Users` (`Email`, `Firstname`, `Lastname`, `Level`, `Activated`) VALUES ('system', 'System', 'User', '99', '1');";

    if (file_exists(__DIR__ . "/sql/users.sql")) {
        echo "Found user backup!" . PHP_EOL;
        $SQL .= file_get_contents(__DIR__ . "/sql/users.sql");
    }
    echo $SQL . PHP_EOL;
    $Mysql = new \crisp\core\MySQL();

    $DB = $Mysql->getDBConnector();

    $DB->beginTransaction();

    $DB->query($SQL);

    $DB->commit();
});


$this->registerUninstallHook(function($Callback) {
    echo "Removing SQL Tables" . PHP_EOL;
    $SQL = "DROP TABLE `Sessions`;"
            . "DROP TABLE `Users`";

    echo $SQL . PHP_EOL;

    $Mysql = new \crisp\core\MySQL();

    $DB = $Mysql->getDBConnector();


    $statement = $DB->prepare("SELECT * FROM Users");
    $statement->execute();

    $Array = array();

    foreach ($statement->fetchAll(\PDO::FETCH_ASSOC) as $User) {
        if ($User["ID"] === "1") {
            continue;
        }
        $Array[] = strtr("INSERT INTO `Users` (`ID`, `Email`, `Firstname`, `Lastname`, `Password`, `Level`, `Activated`, `CreatedAt`, `UpdatedAt`) VALUES (':ID', ':Email', ':Firstname', ':Lastname', ':Password', ':Level', ':Activated', ':CreatedAt', ':UpdatedAt');", array(
            ":ID" => $User["ID"],
            ":Email" => $User["Email"],
            ":Firstname" => $User["Firstname"],
            ":Lastname" => $User["Lastname"],
            ":Password" => $User["Password"],
            ":Level" => $User["Level"],
            ":Activated" => $User["Activated"],
            ":CreatedAt" => $User["CreatedAt"],
            ":UpdatedAt" => $User["UpdatedAt"]
        ));
    }

    $written = file_put_contents(__DIR__ . "/sql/users.sql", implode(PHP_EOL, $Array));

    if ($written) {
        echo "Exported to sql/users.sql" . PHP_EOL;
    } else {
        echo "Failed to export!" . PHP_EOL;
    }


    $DB->beginTransaction();

    $DB->query($SQL);

    $DB->commit();
});


\crisp\core\Template::addtoNavbar("login", $this->getTranslation("login"), "/login");
