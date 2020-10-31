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

define('NO_KMS', true);
ob_start();

if (php_sapi_name() !== 'cli') {
    echo "Not from CLI";
    ob_end_clean();
    exit;
}

declare(ticks=1);

error_reporting(error_reporting() & ~E_NOTICE);
require_once __DIR__ . "/../pixelcatproductions/crisp.php";

$Error = false;
$Log;
$runningJob;

function terminateJob($Reason) {
    global $Error;
    global $Log;
    global $runningJob;
    consoleLog("===== JOB FAILED =====");
    consoleLog("REASON: $Reason");
    $Error = true;
    \crisp\api\lists\Cron::markAsFailed($runningJob);
    \crisp\api\lists\Cron::setLog($runningJob, $Log);
}

function handleSignal() {
    global $Log;
    global $runningJob;
    consoleLog("===== JOB TERMINATED =====");
    \crisp\api\lists\Cron::markAsFailed($runningJob);
    \crisp\api\lists\Cron::setLog($runningJob, $Log);
    exit;
}

function consoleLog($String) {
    global $Log;
    global $runningJob;
    echo $String . \PHP_EOL;
    $Log .= \ob_get_contents();
    \ob_flush();
    if ($runningJob !== null) {
        \crisp\api\lists\Cron::setLog($runningJob, $Log);
    }
}

// Install the signal handlers
set_exception_handler('terminateJob');
pcntl_signal(SIGHUP, 'handleSignal');
pcntl_signal(SIGINT, 'handleSignal');
pcntl_signal(SIGTERM, 'handleSignal');

if (!$KMS->LicenseMetadata->cron) {
    consoleLog("Crons are not enabled!");
    exit;
}

$Jobs = \crisp\api\lists\Cron::fetchUnprocessedSchedule(5);

foreach ($Jobs as $Job) {

    $Log = "";

    $runningJob = $Job["ID"];
    consoleLog("===== JOB PROCESSING =====");

    \crisp\api\lists\Cron::markAsStarted($Job["ID"]);

    if ($Job["Type"] === "anonymize") {
        if (!isset($Job["Data"]) || !\is_numeric($Job["Data"])) {
            terminateJob("Missing or corrupt data!");
            continue;
        }

        consoleLog("Searching for user...");


        $User = new \crisp\api\User($Job["Data"]);
        $UserData = $User->fetch();

        if ($UserData === null) {
            \terminateJob("User does not exist!");
            continue;
        }
        consoleLog("Found user!");

        consoleLog("===== USER DETAILS =====");
        consoleLog("ID: " . $User->UserID);
        consoleLog("Firstname: " . $User->getFirstname());
        consoleLog("Lastname: " . $User->getLastname());
        consoleLog("Email: " . $User->getEmail());
        consoleLog("Level: " . $User->getLevel());
        consoleLog("Balance: " . $User->getBalance());
        consoleLog("===== END USER DETAILS =====");



        consoleLog("Attempting to anonymize user:");
        consoleLog("Enabling MySQL Transaction");


        if (!$User->enableTransaction()) {
            \terminateJob("Failed to enable MySQL Transaction");
            continue;
        }
        consoleLog("Enabled MySQL Transaction");


        consoleLog("Setting Firstname...");

        if (!$User->setFirstname("Anonymized")) {
            consoleLog("Rolling back...");
            $User->rollBackTransaction();
            \terminateJob("Failed to set firstname!");
            continue;
        }
        consoleLog("Firstname successfully set!");



        consoleLog("Removing Lastname...");

        if (!$User->setLastname(NULL)) {
            consoleLog("Rolling back...");
            $User->rollBackTransaction();
            \terminateJob("Failed to remove lastname!");
            continue;
        }


        consoleLog("Lastname successfully removed!");



        consoleLog("Disabling account...");
        if (!$User->deactivate()) {
            consoleLog("Rolling back...");
            $User->rollBackTransaction();
            \terminateJob("Failed to disable account!");
            continue;
        }

        consoleLog("Account successfully disabled!");




        consoleLog("Resetting balance...");
        if (!$User->setBalance("0")) {
            consoleLog("Rolling back...");
            $User->rollBackTransaction();
            \terminateJob("Failed to reset balance!");
            continue;
        }


        consoleLog("Balance successfully reset!");



        consoleLog("Anonymizing E-Mail...");
        if (!$User->setEmail(\crisp\core\Crypto::UUIDv4() . "@anonymized.crisp")) {
            consoleLog("Rolling back...");
            $User->rollBackTransaction();
            \terminateJob("Failed to anonymize email!");
            continue;
        }

        consoleLog("Email successfully anonymized!");



        consoleLog("Downgrading Account to dummy...");
        if (!$User->setLevel(0)) {
            consoleLog("Rolling back...");
            $User->rollBackTransaction();
            \terminateJob("Failed to downgrade account!");
            continue;
        }

        consoleLog("Account successfully downgraded!");




        consoleLog("Gathering all photos of the user...");


        $Photos = \crisp\api\lists\Photos::fetchAllByUser($Job["ID"], true, null);

        consoleLog("Found " . count($Photos) . " photos from user!");

        consoleLog("Disabling all photos...");

        foreach ($Photos as $Photo) {

            consoleLog("Enabling MySQL Transaction");


            if (!$Photo->enableTransaction()) {
                \terminateJob("Failed to enable MySQL Transaction");
                continue;
            }
            consoleLog("Enabled MySQL Transaction");

            consoleLog("Disabling Photo #" . $Photo->PhotoID);

            if (!$Photo->disable()) {
                consoleLog("Rolling back...");
                $Photo->rollBackTransaction();
                \terminateJob("Failed to disable photo");
                continue;
            }
            consoleLog("Photo has been successfully disabled");
            consoleLog("Comitting PHOTO Transaction...");
            if (!$Photo->commitTransaction()) {
                consoleLog("Rolling back...");
                $Photto->rollBackTransaction();
                \terminateJob("Failed to commit PHOTO transaction!");
                continue;
            }
        }
        consoleLog("All photos have been disabled!");



        consoleLog("Deleting all sessions");
        if (!\crisp\api\lists\Sessions::deleteByUser($Job["Data"])) {
            consoleLog("Rolling back...");
            $User->rollBackTransaction();
            \terminateJob("Failed to delete all sessions!");
            continue;
        }


        consoleLog("Comitting USER Transaction...");
        if (!$User->commitTransaction()) {
            consoleLog("Rolling back...");
            $User->rollBackTransaction();
            \terminateJob("Failed to commit USER transaction!");
            continue;
        }


        consoleLog("Account successfully anonymized!");
    } elseif ($Job["Type"] === 'toggle_verification') {
        if (!isset($Job["Data"]) || !\is_numeric($Job["Data"])) {
            terminateJob("Missing or corrupt data!");
            continue;
        }

        consoleLog("Searching for user...");


        $User = new \crisp\api\User($Job["Data"]);
        $UserData = $User->fetch();

        if ($UserData === null) {
            \terminateJob("User does not exist!");
            continue;
        }
        consoleLog("Found user!");

        consoleLog("===== USER DETAILS =====");
        consoleLog("ID: " . $User->UserID);
        consoleLog("Firstname: " . $User->getFirstname());
        consoleLog("Lastname: " . $User->getLastname());
        consoleLog("Email: " . $User->getEmail());
        consoleLog("Level: " . $User->getLevel());
        consoleLog("Balance: " . $User->getBalance());
        consoleLog("===== END USER DETAILS =====");


        if ($User->getVerified()) {
            consoleLog("Removing Verification Status...");
            if (!$User->unverify()) {
                \terminateJob("Failed to unverify user!");
                continue;
            }
            consoleLog("Successfully unverified user!");
        } else {
            consoleLog("Adding Verification Status...");
            if (!$User->verify()) {
                \terminateJob("Failed to verify user!");
                continue;
            }
            consoleLog("Successfully verified user!");
        }
    } elseif ($Job["Type"] === 'approve_photo') {

        if (!isset($Job["Data"]) || !\crisp\api\Helper::isSerialized($Job["Data"])) {
            terminateJob("Missing or corrupt data!");
            continue;
        }

        $Data = \unserialize($Job["Data"]);

        consoleLog("Searching for photo...");


        $Photo = new \crisp\api\Photo($Data["ID"]);
        $PhotoData = $Photo->fetch();

        if ($Photo === null) {
            \terminateJob("Photo does not exist!");
            continue;
        }
        consoleLog("Found photo!");


        consoleLog("Enabling MySQL Transaction");


        if (!$Photo->enableTransaction()) {
            \terminateJob("Failed to enable MySQL Transaction");
            continue;
        }
        consoleLog("Enabled MySQL Transaction");


        consoleLog("Setting Title...");

        if (!$Photo->setTitle($Data["title"])) {
            \consoleLog("Rolling back...");
            $Photo->rollBackTransaction();
            \terminateJob("Failed to set Title!");
            continue;
        }
        \consoleLog("Title successfully set!");


        consoleLog("Setting Description...");

        if (!$Photo->setDescription($Data["description"])) {
            \consoleLog("Rolling back...");
            $Photo->rollBackTransaction();
            \terminateJob("Failed to set Description!");
            continue;
        }
        \consoleLog("Description successfully set!");


        consoleLog("Setting Coordinates...");

        if (!$Photo->setCoordinates($Data["latitude"], $Data["longitude"])) {
            \consoleLog("Rolling back...");
            $Photo->rollBackTransaction();
            \terminateJob("Failed to set Coordinates!");
            continue;
        }
        \consoleLog("Coordinates successfully set!");


        consoleLog("Setting Type...");

        if (!$Photo->setType($Data["type"])) {
            \consoleLog("Rolling back...");
            $Photo->rollBackTransaction();
            \terminateJob("Failed to set Type!");
            continue;
        }
        \consoleLog("Type successfully set!");


        consoleLog("Setting Marker...");

        if (!$Photo->setMarker($Data["marker"])) {
            \consoleLog("Rolling back...");
            $Photo->rollBackTransaction();
            \terminateJob("Failed to set Marker!");
            continue;
        }
        \consoleLog("Marker successfully set!");


        \consoleLog("Contacting Stripe to create Product...");

        if (!$Photo->generateStripeProduct()) {
            \consoleLog("Rolling back...");
            $Photo->rollBackTransaction();
            \terminateJob("Failed to contact Stripe!");
            continue;
        }
        \consoleLog("Successfully contacted Stripe...");



        \consoleLog("Parsing PRICE array...");
        \consoleLog(var_export($Data["size_price"], true));
        foreach ($Data["size_price"] as $TypeID => $TypePrices) {
            consoleLog("Found Type: $TypeID");
            foreach ($TypePrices as $PriceID => $Price) {
                if ($Price == "0.00") {
                    consoleLog("Skipping empty price...");
                    continue;
                }
                consoleLog("Found " . $Price . "€ for $PriceID");

                consoleLog("Setting Price...");



                if (!$Photo->setPrice($PriceID, $Price, $TypeID)) {
                    \consoleLog("Rolling back...");
                    $Photo->rollBackTransaction();
                    \terminateJob("Failed to set Price!");
                    continue;
                }
                \consoleLog("Price successfully set!");


                \consoleLog("Contacting Stripe to create SKU...");

                $sku = $Photo->generateStripeSKU($PriceID, $TypeID);

                if (!$sku) {
                    \consoleLog("Rolling back...");
                    $Photo->rollBackTransaction();
                    \terminateJob("Failed to contact Stripe!");
                    continue;
                }
                \consoleLog("Successfully contacted Stripe...");
            }
        }


        \consoleLog("Parsed PRICE array");


        \consoleLog("Parsing STOCK array...");
        \consoleLog(var_export($Data["size_stock"], true));
        foreach ($Data["size_stock"] as $TypeID => $TypeStock) {
            consoleLog("Found Type: $TypeID");
            foreach ($TypeStock as $StockID => $Stock) {
                consoleLog("Found $Stock for $StockID");

                consoleLog("Setting Stock...");

                if (!$Photo->setStock($StockID, $Price, $TypeID)) {
                    \consoleLog("Rolling back...");
                    $Photo->rollBackTransaction();
                    \terminateJob("Failed to set Stock!");
                    continue;
                }
                \consoleLog("Stock successfully set!");
            }
        }


        \consoleLog("Parsed STOCK array");


        \consoleLog("Enabling photo");

        if (!$Photo->enable()) {
            \consoleLog("Rolling back...");
            $Photo->rollBackTransaction();
            \terminateJob("Failed to enable photo!");
            continue;
        }
        consoleLog("Successfully enabled photo!");



        \consoleLog("Making photo visible");

        if (!$Photo->show()) {
            \consoleLog("Rolling back...");
            $Photo->rollBackTransaction();
            \terminateJob("Failed to make photo visible!");
            continue;
        }
        consoleLog("Successfully made photo visible!");

        \consoleLog("Committing transaction...");
        if (!$Photo->commitTransaction()) {
            \consoleLog("Rolling back...");
            $Photo->rollBackTransaction();
            \terminateJob("Failed to commit transaction!");
            continue;
        }
        consoleLog("Successfully committed transaction!");
    } elseif ($Job["Type"] === "generate_tags") {

        $Tags = array();

        consoleLog("Fetching all languages...");

        foreach (\crisp\api\Translation::listLanguages() as $Language) {

            $Code = $Language->getCode();
            consoleLog("Fetched " . $Language->getName() . "!");
            $diffTranslation = new \crisp\api\Translation($Language->getCode());


            consoleLog("Fetching all Tags and translating them...");
            foreach (\crisp\api\lists\Tags::fetchAll() as $Tag) {

                consoleLog("Fetched " . $Tag["translationkey"] . "!");
                $Tags[] = array("value" => $Tag["ID"], "text" => $diffTranslation::fetch($Tag["translationkey"]));
            }
            consoleLog("Writing tags to raw json file...");
            if (!\file_put_contents(__DIR__ . "/../ajax/cron/tags_$Code.json", \json_encode($Tags, \JSON_PRETTY_PRINT))) {
                \terminateJob("Failed to write raw json file!");
                continue;
            }
            consoleLog("Successfully written tags to raw son file!");
            consoleLog("Writing tags to minified json file...");
            if (!\file_put_contents(__DIR__ . "/../ajax/cron/tags_$Code.min.json", \json_encode($Tags))) {
                \terminateJob("Failed to write minified json file!");
                continue;
            }
            consoleLog("Successfully written tags to minified json file!");
            $Tags = array();
        }
    } elseif ($Job["Type"] === "generate_categories") {

        $Tags = array();

        consoleLog("Fetching all languages...");

        foreach (\crisp\api\Translation::listLanguages() as $Language) {

            $Code = $Language->getCode();
            consoleLog("Fetched " . $Language->getName() . "!");
            $diffTranslation = new \crisp\api\Translation($Language->getCode());


            consoleLog("Fetching all Tags and translating them...");
            foreach (\crisp\api\lists\Categories::fetchAll() as $Tag) {

                consoleLog("Fetched " . $Tag["Category"] . "!");
                $Tags[$Tag["ID"]] = array("icon" => $Tag["Icon"], "text" => $diffTranslation::fetch($Tag["Category"]));
            }
            consoleLog("Writing categories to raw json file...");
            if (!\file_put_contents(__DIR__ . "/../ajax/cron/categories_$Code.json", \json_encode($Tags, \JSON_PRETTY_PRINT))) {
                \terminateJob("Failed to write raw json file!");
                continue;
            }
            consoleLog("Successfully written categories to raw son file!");
            consoleLog("Writing categories to minified json file...");
            if (!\file_put_contents(__DIR__ . "/../ajax/cron/categories_$Code.min.json", \json_encode($Tags))) {
                \terminateJob("Failed to write minified json file!");
                continue;
            }
            consoleLog("Successfully written categories to minified json file!");
            $Tags = array();
        }
    } elseif ($Job["Type"] === "update_git") {


        chdir(dirname(__FILE__));
        consoleLog("Attempting to update LophotenCMS from Github");
        consoleLog("Current Revision is " . crisp\api\Helper::getGitRevision());

        consoleLog("Checking for an update before pulling...");
        if (trim(crisp\api\Helper::getGitRevision()) == trim(crisp\api\Helper::getLatestGitRevision(true))) {
            consoleLog("Seems there is no update available and you are on the latest revision!");
        } else {

            consoleLog("Update found! Newest revision is " . crisp\api\Helper::getLatestGitRevision());
            consoleLog("Updating from Github...");
            $Update = exec("pwd && cd .. & git pull");



            consoleLog($Update);

            consoleLog("Update from Github complete.");
            \crisp\api\Config::set("github_current_revision", crisp\api\Helper::getGitRevision());
        }
    } else {
        \terminateJob("Invalid type!");
        continue;
    }



    if (!$Error) {

        consoleLog("===== JOB PROCESSED =====");

        \crisp\api\lists\Cron ::markAsFinished($Job["ID"]);
        \crisp\api\lists\Cron::setLog($Job["ID"], $Log);
    }
}

if (count($Jobs) == 0) {
    consoleLog("No cron jobs executed, everything already processed!");
}
pcntl_signal(SIGHUP, SIG_DFL);
pcntl_signal(SIGINT, SIG_DFL);
pcntl_signal(SIGTERM, SIG_DFL);

ob_end_clean();
