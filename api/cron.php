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




define('NO_KMS', true);
define('CRISP_API', true);
ob_start();

if (php_sapi_name() !== 'cli') {
    echo "Not from CLI";
    ob_end_clean();
    exit;
}

declare(ticks=1);

error_reporting(error_reporting() & ~E_NOTICE);
require_once __DIR__ . "/../tosdr/crisp.php";

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


$_CRONs = \crisp\api\lists\Cron::fetchUnprocessedSchedule(15);
try {
    foreach ($_CRONs as $_CRON) {

        $Log = "";

        $runningJob = $_CRON["ID"];
        consoleLog("===== JOB #$runningJob PROCESSING =====");

        \crisp\api\lists\Cron::markAsStarted($runningJob);
        if ($_CRON["Type"] === "execute_plugin_cron") {

            $_CRON["Data"] = json_decode($_CRON["Data"]);

            consoleLog("Executing cron job for plugin " . $_CRON["Plugin"]);

            if (file_exists(__DIR__ . "/../plugins/" . $_CRON["Plugin"] . "/includes/cron.php")) {
                consoleLog("Including cron file");
                require __DIR__ . "/../plugins/" . $_CRON["Plugin"] . "/includes/cron.php";
                consoleLog("Cron file included!");
            } else {
                \terminateJob("Plugin has no cron file!");
            }
        } else {
            \terminateJob("Invalid type!");
            continue;
        }



        if (!$Error) {

            consoleLog("===== JOB #$runningJob PROCESSED =====");

            \crisp\api\lists\Cron::markAsFinished($runningJob);
            \crisp\api\lists\Cron::setLog($runningJob, $Log);
        }
    }
} catch (\Exception $ex) {
    \terminateJob("Exception!\n$ex");
}

if (count($_CRONs) == 0) {
    consoleLog("No cron jobs executed, everything already processed!");
}
pcntl_signal(SIGHUP, SIG_DFL);
pcntl_signal(SIGINT, SIG_DFL);
pcntl_signal(SIGTERM, SIG_DFL);

ob_end_clean();

crisp\api\lists\Cron::deleteOld();
