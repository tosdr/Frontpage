<?php

/*
 * Cron info is saved in $_CRON
 */

/**
 * We should ALWAYS mark a cron else it will stuck running!
 */
/**
 * Please know that the cron file will NOT inherit plugin properties. meaning Config::get will not work like the way it does in the plugin files.
 * To get the config "helloworld" in the plugin you would have to run Config::get("plugin_PLUGINNAME_helloworld") here
 */
if (isset($_CRON)) {

    $Key = "plugin_" . $_CRON["Data"]->plugin . "_database_uri";

    consoleLog($Key);



    $Test = \crisp\api\Config::set($Key, trim(shell_exec("/usr/local/bin/heroku config:get DATABASE_URL -a edit-tosdr-org")));

    consoleLog("Setting Config: $Test");


    \crisp\api\lists\Cron::markAsFinished($_CRON["ID"]);
    \crisp\api\lists\Cron::deleteOld();
    \crisp\api\lists\Cron::create("execute_plugin_cron", json_encode(array("plugin" => $_CRON->Data["plugin"], "data" => "I am data", "name" => "update_heroku")), $_CRON["Interval"]);
}

define('CRISP_CLI', true);
define('CRISP_API', true);
define('NO_KMS', true);
error_reporting(error_reporting() & ~E_NOTICE);
require_once __DIR__ . "/../../../pixelcatproductions/crisp.php";
\crisp\api\lists\Cron::create("execute_plugin_cron", json_encode(array("plugin" => "heroku", "data" => "I am data", "name" => "update_heroku")), "5 MINUTE");
