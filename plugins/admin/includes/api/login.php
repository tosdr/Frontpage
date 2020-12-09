<?php

/** @var \crisp\core\PluginAPI $this */
/** @var \crisp\core\PluginAPI self */
include __DIR__ . '/../User.php';
include __DIR__ . '/../Users.php';




$User = \crisp\plugin\admin\Users::fetchByEmail($_POST["email"]);

if (!$User->UserID) {
    $this->response(array("INVALID_EMAIL"), "E-Mail is invalid");
    exit;
}

if (!$User->verifyPassword($_POST["password"])) {
    $this->response(array("INVALID_PASSWORD"), "Password is invalid");
    exit;
}

if (!$User->createSession()) {
    $this->response(array("SESSION_ERROR"), "Failed to create session");
    exit;
}
$this->response(false, "OK");
