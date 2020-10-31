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
header("Content-Type: application/json");
require_once __DIR__ . "/../pixelcatproductions/crisp.php";

$Translation = new \crisp\api\Translation($Locale);

if (!isset($_POST["code"])) {
    echo json_encode(array("error" => true, "text" => $Translation::fetch("error_language_not_defined")));
    return;
}

if (!\crisp\api\lists\Languages::getLanguageByCode(filter_input(INPUT_POST, "code"))) {
    echo json_encode(array("error" => true, "text" => $Translation::fetch("error_language_not_available")));
    return;
}

$NewTranslation = new \crisp\api\Translation(filter_input(INPUT_POST, "code"));


if (!isset($_SESSION[\crisp\core\Config::$Cookie_Prefix . "session_login"])) {

    setcookie(\crisp\core\Config::$Cookie_Prefix . "language", filter_input(INPUT_POST, "code"), time() + (86400 * 30), "/");
    if (count($_COOKIE) > 0) {
        echo json_encode(array("error" => false, "text" => $NewTranslation::fetch("success_language_change")));
    } else {
        echo json_encode(array("error" => true, "text" => $NewTranslation::fetch("error_language_no_cookies")));
    }
} else {


    $User = new crisp\api\User(CURRENT_USER["ID"]);

    $Language = \crisp\api\lists\Languages::getLanguageByCode(filter_input(INPUT_POST, "code"));

    $LangChanged = $User->setLanguage($Language->LanguageID);

    if ($LangChanged) {
        echo json_encode(array("error" => false, "text" => $NewTranslation::fetch("success_language_change")));
        exit;
    }
    echo json_encode(array("error" => true, "text" => $NewTranslation::fetch("error_language_session_error")));
    exit;
}