<?php

/** @var \crisp\core\PluginAPI $this */
/** @var \crisp\core\PluginAPI self */
include __DIR__ . '/../Users.php';
include __DIR__ . '/../PhoenixUser.php';




$User = \crisp\plugin\curator\Users::fetchByEmail($_POST["email"]);

if (!$User) {
    $this->response(array("INVALID_EMAIL", "INVALID_PASSWORD"), "Error");
    exit;
}

if ($User->UserID === false) {
    $this->response(array("INVALID_EMAIL"), "E-Mail is invalid");
    exit;
}

if (!$User->verifyPassword($_POST["password"])) {
    $this->response(array("INVALID_PASSWORD"), "Password is invalid");
    exit;
}
if (!$User->fetch()["curator"]) {
    $this->response(array("NOT_CURATOR"), "You are not an curator!");
    exit;
}
if (!$User->createSession()) {
    $this->response(array("SESSION_ERROR"), "Failed to create session");
    exit;
}
$this->response(false, "OK");
