<?php

/** @var \crisp\core\PluginAPI $this */
/** @var \crisp\core\PluginAPI self */
include __DIR__ . '/../Users.php';
include __DIR__ . '/../PhoenixUser.php';


if (!isset($_SESSION[crisp\core\Config::$Cookie_Prefix . "_csrf"])) {
    $_SESSION[crisp\core\Config::$Cookie_Prefix . "_csrf"] = crisp\core\Crypto::UUIDv4("csrf_");
}


if (!isset($_SESSION[\crisp\core\Config::$Cookie_Prefix . "session_login"])) {
    $this->response(["SESSION_INVALID"], "Session is invalid");
    exit;
}

$User = new crisp\plugin\admin\PhoenixUser($_SESSION[\crisp\core\Config::$Cookie_Prefix . "session_login"]["User"]);

if (!$User->isSessionValid() || CURRENT_UNIVERSE != crisp\Universe::UNIVERSE_TOSDR) {
    $this->response(["INVALID_UNIVERSE", "SESSION_INVALID"], "Session or universe is invalid");
    exit;
}

if (!isset($_POST["action"]) || empty($_POST["action"])) {
    $this->response(["MISSING_PARAMETER"], "Missing parameter \"action\"", [], null, 400);
    exit;
}
if (!isset($_POST["csrf"]) || empty($_POST["csrf"])) {
    $this->response(["MISSING_PARAMETER"], "Missing parameter \"csrf\"", [], null, 400);
    exit;
}
if (!isset($_POST["plugin"]) || empty($_POST["plugin"])) {
    $this->response(["MISSING_PARAMETER"], "Missing parameter \"plugin\"", [], null, 400);
    exit;
}

if ($_POST["csrf"] !== $_SESSION[crisp\core\Config::$Cookie_Prefix . "_csrf"]) {
    $this->response(["CSRF_MISMATCH"], \crisp\api\Translation::fetch("plugin_admin_csrf_mismatch"), [], null, 403);
}
switch ($_POST["action"]) {
    case "integrity":
        $check = \crisp\core\Plugins::integrityCheck($_POST["plugin"], \crisp\core\Plugins::getPluginMetadata($_POST["plugin"]));
        if ($check["integrity"]) {
            $this->response(false, \crisp\api\Translation::fetch("plugin_admin_integrity_ok"));
        } else {
            $this->response(["CHECK_FAILED"], \crisp\api\Translation::fetch("plugin_admin_integrity_failed"));
        }
        break;
    case "uninstall":
        if (!\crisp\core\Plugins::isInstalled($_POST["plugin"])) {
            $this->response(["UNINSTALL_FAILED"], \crisp\api\Translation::fetch("plugin_admin_uninstall_failed"));
            exit;
        }
        $check = \crisp\core\Plugins::uninstall($_POST["plugin"], null, __FILE__, "admin_plugins");
        if ($check) {
            $this->response(false, \crisp\api\Translation::fetch("plugin_admin_uninstalled"));
        } else {
            $this->response(["UNINSTALL_FAILED"], \crisp\api\Translation::fetch("plugin_admin_uninstall_failed"));
        }
        break;
    case "install":
        if (\crisp\core\Plugins::isInstalled($_POST["plugin"])) {
            $this->response(["INSTALL_FAILED"], \crisp\api\Translation::fetch("plugin_admin_install_failed"));
            exit;
        }
        $check = \crisp\core\Plugins::install($_POST["plugin"], null, __FILE__, "admin_plugins");
        if ($check) {
            $this->response(false, \crisp\api\Translation::fetch("plugin_admin_installed"));
        } else {
            $this->response(["INSTALL_FAILED"], \crisp\api\Translation::fetch("plugin_admin_install_failed"));
        }
        break;
    case "refresh_storage":
        if (!\crisp\core\Plugins::isInstalled($_POST["plugin"])) {
            $this->response(["REFRESH_STORAGE_FAILED"], \crisp\api\Translation::fetch("plugin_admin_refresh_storage_failed"));
            exit;
        }
        $check = \crisp\core\Plugins::installKVStorage($_POST["plugin"], \crisp\core\Plugins::getPluginMetadata($_POST["plugin"]));
        if ($check) {
            $this->response(false, \crisp\api\Translation::fetch("plugin_admin_refreshed"));
        } else {
            $this->response(["REFRESH_STORAGE_FAILED"], \crisp\api\Translation::fetch("admin_refresh_storage_failed"));
        }
        break;
    case "refresh_translations":
        if (!\crisp\core\Plugins::isInstalled($_POST["plugin"])) {
            $this->response(["REFRESH_TRANSLATIONS_FAILED"], \crisp\api\Translation::fetch("admin_refresh_translations_failed"));
            exit;
        }
        $check = \crisp\core\Plugins::installTranslations($_POST["plugin"], \crisp\core\Plugins::getPluginMetadata($_POST["plugin"]));
        if ($check) {
            $this->response(false, \crisp\api\Translation::fetch("plugin_admin_refreshed"));
        } else {
            $this->response(["REFRESH_TRANSLATIONS_FAILED"], \crisp\api\Translation::fetch("admin_refresh_translations_failed"));
        }
        break;

    default:

        $this->response(["INVALID_ACTION"], "Not implemented", [], null, 501);
}
