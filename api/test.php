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

$Jobs = \crisp\api\lists\Cron::fetchUnprocessedSchedule(5);

foreach ($Jobs as $Job) {

    $Log = "";

    $runningJob = $Job["ID"];
    consoleLog("===== JOB PROCESSING =====");

    \crisp\api\lists\Cron::markAsStarted($Job["ID"]);
    if ($Job["Type"] === 'approve_photo') {

        if (!isset($Job["Data"]) || !\crisp\api\Helper::isSerialized($Job["Data"])) {
            terminateJob("Missing or corrupt data!");
            continue;
        }

        $Data = \unserialize($Job["Data"]);

        
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