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

/** @var crisp\core\Plugin $this */
/**
 * This file demonstrates how to make use of the plugin un/install hooks and creates example entries in the KV Storage
 * 
 * All plugins are in the crisp\core\Plugin context, feel free to use all $this methods, as it's easier to manage than crisp\core\Plugins with all variables set
 * 
 * Please note that the preferred method is to create an onInstall event in the plugin.json as it automatically creates all Keys during the installation
 * thats why the methods have been commented out.
 */
$hookInstallRegistered = $this->registerInstallHook(function() {
    echo "Plugin installed!";

    /*
      $this->createConfig("test_1", "I work!");
      $this->createConfig("test_2", "I work!");
      $this->createConfig("test_3", "I work!");
      $this->createConfig("test_4", "I work!");
      $this->createConfig("test_5", "I work!");
      $this->createConfig("test_6", "I work!");
      $this->createConfig("test_7", "I work!");
     */
});
$hookUninstallRegistered = $this->registerUninstallHook(function() {
    echo "Plugin uninstalled!";
    /*
      $this->deleteConfig("test_1");
      $this->deleteConfig("test_2");
      $this->deleteConfig("test_3");
      $this->deleteConfig("test_4");
      $this->deleteConfig("test_5");
      $this->deleteConfig("test_6");
      $this->deleteConfig("test_7");
     */
});


if ($hookInstallRegistered) {
    echo "Register hook created<br>";
}

if ($hookUninstallRegistered) {
    echo "Unregister hook created<br>";
}
