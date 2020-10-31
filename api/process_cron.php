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

if (php_sapi_name() !== 'cli') {
    echo "Not from CLI";
    exit;
}
error_reporting(error_reporting() & ~E_NOTICE);
require_once __DIR__ . "/../pixelcatproductions/crisp.php";



foreach (\crisp\api\lists\ProcessJobs::fetchUnprocessedSchedule(5) as $Job) {

    try {
        $Photo = new \crisp\api\Photo($Job["Photo"]);


        $error = false;

        echo "===== Processing Image $Photo->PhotoID =====" . \PHP_EOL;


        echo "Executing ProcessJobs::markAsStarted: ";
        $progress[$Job["Photo"]]["ProcessJobs::markAsStarted"] = \crisp\api\lists\ProcessJobs::markAsStarted($Job["ID"]);
        \var_dump($progress[$Job["Photo"]]["ProcessJobs::markAsStarted"]);
        echo PHP_EOL;

        echo "Executing Photo->generateMapThumbnail: ";
        $progress[$Job["Photo"]]["Photo->generateMapThumbnail"] = ($Photo->generateMapThumbnail());
        \var_dump($progress[$Job["Photo"]]["Photo->generateMapThumbnail"]);
        echo PHP_EOL;

        echo "Executing Photo->generateWebThumbnail: ";
        $progress[$Job["Photo"]]["Photo->generateWebThumbnail"] = ($Photo->generateWebThumbnail());
        \var_dump($progress[$Job["Photo"]]["Photo->generateWebThumbnail"]);
        echo PHP_EOL;

        echo "Executing Photo->generateWebLargeThumbnail: ";
        $progress[$Job["Photo"]]["Photo->generateWebLargeThumbnail"] = ($Photo->generateWebLargeThumbnail());
        \var_dump($progress[$Job["Photo"]]["Photo->generateWebLargeThumbnail"]);
        echo PHP_EOL;

        echo "Executing Photo->generateMockup: ";
        $progress[$Job["Photo"]]["Photo->generateMockup"] = ($Photo->generateMockup());
        \var_dump($progress[$Job["Photo"]]["Photo->generateMockup"]);
        echo PHP_EOL;

        echo "Executing ProcessJobs::markAsFinished: ";
        $progress[$Job["Photo"]]["ProcessJobs::markAsFinished"] = (\crisp\api\lists\ProcessJobs::markAsFinished($Job["ID"]));
        \var_dump($progress[$Job["Photo"]]["ProcessJobs::markAsFinished"]);
        echo PHP_EOL;

        if (!$progress[$Job["Photo"]]["ProcessJobs::markAsStarted"]) {
            $error = true;
        }

        if (!$progress[$Job["Photo"]]["Photo->generateMapThumbnail"]) {
            $error = true;
        }
        if (!$progress[$Job["Photo"]]["Photo->generateWebThumbnail"]) {
            $error = true;
        }
        if (!$progress[$Job["Photo"]]["Photo->generateWebLargeThumbnail"]) {
            $error = true;
        }
        if (!$progress[$Job["Photo"]]["Photo->generateMockup"]) {
            $error = true;
        }
        if (!$progress[$Job["Photo"]]["ProcessJobs::markAsFinished"]) {
            $error = true;
        }

        if ($error) {
            echo "Marking Job as Failed: ";
            $progress[$Job["Photo"]]["ProcessJobs::markAsFailed"] = \crisp\api\lists\ProcessJobs::markAsFailed($Job["ID"]);
            echo PHP_EOL;
            \var_dump($progress[$Job["Photo"]]["ProcessJobs::markAsFailed"]);
        }

        echo "===== Processed Image $Photo->PhotoID =====" . \PHP_EOL;
    } catch (Exception $ex) {
        echo "Marking Job as Failed: ";
        $progress[$Job["Photo"]]["ProcessJobs::markAsFailed"] = \crisp\api\lists\ProcessJobs::markAsFailed($Job["ID"]);
        echo PHP_EOL;
        \var_dump($progress[$Job["Photo"]]["ProcessJobs::markAsFailed"]);
        echo PHP_EOL;
        echo var_export($Job, true);
    }
}

if (count($progress) == 0) {
    echo "No cron jobs executed, everything already processed!" . \PHP_EOL;
}