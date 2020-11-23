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
    consoleLog("===== JOB #$runningJob FAILED =====");
    consoleLog("REASON: $Reason");
    $Error = true;
    \crisp\api\lists\Cron::markAsFailed($runningJob);
    \crisp\api\lists\Cron::setLog($runningJob, $Log);
}

function handleSignal() {
    global $Log;
    global $runningJob;
    consoleLog("===== JOB #$runningJob TERMINATED =====");
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


$Jobs = \crisp\api\lists\Cron::fetchUnprocessedSchedule(15);

foreach ($Jobs as $Job) {

    $Log = "";

    $runningJob = $Job["ID"];
    consoleLog("===== JOB #$runningJob PROCESSING =====");

    \crisp\api\lists\Cron::markAsStarted($Job["ID"]);
    if ($Job["Type"] === "crawl_service") {

        consoleLog("Crawling Service " . $Job["Data"]);

        $Service = crisp\api\Phoenix::getService($Job["Data"], true);

        consoleLog("Crawled Service #" . $Job["Data"] . ": " . $Service->name);

        consoleLog("Service saved under " . \crisp\api\Config::get("phoenix_api_endpoint") . "/service/name/" . strtolower($Service->name));

        consoleLog("Crawling all points...");

        foreach ($Service->points as $Point) {
            if (!crisp\api\Phoenix::pointExists($Point->id)) {
                consoleLog("Point not yet in REDIS, adding...");
                $AddedServices[$Point->id] = $Point;
                $AddedServices[$Point->id]->cron = \crisp\api\lists\Cron::create("crawl_point", $Point->id);
                consoleLog("Point has been added to the crawling queue! Cron ID #" . $AddedServices[$Point->id]->cron);
            }
        }
    } elseif ($Job["Type"] === "crawl_point") {

        consoleLog("Crawling Point " . $Job["Data"]);

        $Service = crisp\api\Phoenix::getPoint($Job["Data"], true);

        consoleLog("Crawled Point #" . $Job["Data"] . ": " . $Service->id);

        consoleLog("Point saved under " . \crisp\api\Config::get("phoenix_api_endpoint") . "/points/id/" . strtolower($Service->id));
    } elseif ($Job["Type"] === "cron_missing_services") {
        foreach (crisp\api\Phoenix::getServices() as $Service) {
            consoleLog("Found " . $Service->name . " service, checking if dupe.");
            if (!crisp\api\Phoenix::serviceExists($Service->id)) {
                consoleLog("Service not yet in REDIS, adding...");
                $AddedServices[$Service->id] = $Service;
                $AddedServices[$Service->id]->cron = \crisp\api\lists\Cron::create("crawl_service", $Service->id);
                consoleLog("Service has been added to the crawling queue! Cron ID #" . $AddedServices[$Service->id]->cron);
            } else {
                consoleLog("Service is a dupe!");
            }
        }
    } else {
        \terminateJob("Invalid type!");
        continue;
    }



    if (!$Error) {

        consoleLog("===== JOB #$runningJob PROCESSED =====");

        \crisp\api\lists\Cron::markAsFinished($Job["ID"]);
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
