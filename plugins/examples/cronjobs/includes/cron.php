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

$Key = "plugin_" . $_CRON["Data"]->plugin . "_last_executed";

consoleLog($Key);

$Test = \crisp\api\Config::set($Key, time());

consoleLog("Creating Config: $Test");

$Get = \crisp\api\Config::get($Key);

consoleLog("Getting Config: $Get");


\crisp\api\lists\Cron::markAsFinished($_CRON["ID"]);
