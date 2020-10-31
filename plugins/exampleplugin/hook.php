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
 * You can also get the current user by using the CURRENT_USER constant
 */
/**
 * Wohoo! My first plugin
 */
$User = new crisp\api\User(CURRENT_USER["ID"]);

echo $User->fetch()["Firstname"];

/**
 * The testHook returns exactly one parameter!
 * This can be an associative array or a simple array, in this case this is a simple array
 */
function myAwesomeHookFunction($Parameters) {
    echo $Parameters[0]; // Prints "Houston, connection established!"
}

/**
 * Parameter 1 is the hook to listen on
 * Parameter 2 is the function to respond to
 */
$User->on("testHook", 'myAwesomeHookFunction');

############################################################################################

/**
 * The example above wraps the hook into an existing function,
 * what if we want to use anonymous functions? That works aswell!
 */
$User->on("testHook", function ($Parameters) {
    echo $Parameters[0]; // Prints "Houston, connection established!"
});

/**
 * The thing is, we can even listen to multiple hooks at once!
 */
function iListenToMultipleHooks($Parameters) {
    \var_dump($Parameters); // Dumps all variables from all the hooks
}

$User->on('testHook', 'iListenToMultipleHooks');
$User->on('AnotherHook', 'iListenToMultipleHooks');
$User->on('HookMeUp', 'iListenToMultipleHooks');
$User->on('IamAnotherHook', 'iListenToMultipleHooks');
$User->on('LastHookWow', 'iListenToMultipleHooks');
