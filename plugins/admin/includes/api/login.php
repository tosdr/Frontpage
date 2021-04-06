<?php

/** @var \crisp\core\PluginAPI $this */
/** @var \crisp\core\PluginAPI self */
include __DIR__ . '/../Users.php';
include __DIR__ . '/../PhoenixUser.php';




$User = \crisp\plugin\admin\Users::fetchByEmail($_POST["email"]);

if ($User->UserID === false) {
    $this->response(array("INVALID_EMAIL"), "E-Mail is invalid");
    exit;
}

if (!$User->verifyPassword($_POST["password"])) {
    $this->response(array("INVALID_PASSWORD"), "Password is invalid");
    exit;
}
if (!$User->fetch()["admin"]) {
    $this->response(array("NOT_ADMIN"), "You are not an admin!");
    exit;
}
if (!$User->createSession()) {
    $this->response(array("SESSION_ERROR"), "Failed to create session");
    exit;
}
crisp\Universe::changeUniverse(crisp\Universe::UNIVERSE_TOSDR, true);
$this->response(false, "OK");
