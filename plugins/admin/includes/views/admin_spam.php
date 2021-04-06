<?php

/** @var \crisp\core\Plugin $this */
include __DIR__ . '/../Phoenix.php';

if (!isset($_SESSION[\crisp\core\Config::$Cookie_Prefix . "session_login"])) {
    header("Location: /login");
    exit;
}

$User = new crisp\plugin\admin\PhoenixUser($_SESSION[\crisp\core\Config::$Cookie_Prefix . "session_login"]["User"]);
$Array = [];
if (!$User->isSessionValid() || CURRENT_UNIVERSE != crisp\Universe::UNIVERSE_TOSDR) {
    header("Location: /login?oldtoken=" . $_SESSION[\crisp\core\Config::$Cookie_Prefix . "session_login"]["Token"]);
    exit;
}

if (!isset($_SESSION[crisp\core\Config::$Cookie_Prefix . "_csrf"])) {
    $_SESSION[crisp\core\Config::$Cookie_Prefix . "_csrf"] = crisp\core\Crypto::UUIDv4("csrf_");
}
if (isset($_GET["lookup"])) {
    if ($_GET["csrf"] !== $_SESSION[crisp\core\Config::$Cookie_Prefix . "_csrf"]) {
        $_vars["Notice"] = array("Text" => $this->getTranslation("csrf_mismatch"), "Type" => "danger", "Icon" => "fas fa-exclamation-triangle");
    } else {
        switch ($_GET["lookup"]) {
            case "spam_by_comments":
                if ($this->createCron("spam_by_comments", null, "10 SECOND", true)) {
                    $_vars["Notice"] = array("Text" => $this->getTranslation("purge_cron_created") . '<img width="128" src="/plugins/admin/img/gone.gif"/>', "Type" => "success", "Icon" => "fas fa-check");
                }
                break;
        }
    }
} elseif (!isset($_GET["user"])) {
    $_vars["Notice"] = array("Text" => $this->getTranslation("notice_spam_search"), "Type" => "primary", "Icon" => "fas fa-search");
} else {

    if ($_GET["csrf"] !== $_SESSION[crisp\core\Config::$Cookie_Prefix . "_csrf"]) {
        $_vars["Notice"] = array("Text" => $this->getTranslation("csrf_mismatch"), "Type" => "danger", "Icon" => "fas fa-exclamation-triangle");
    } else {

        if (filter_var($_GET["user"], FILTER_VALIDATE_EMAIL) || strpos($_GET["user"], "email:") !== false) {
            $_vars["FoundUser"] = crisp\plugin\admin\Phoenix::fetchByEmail((strpos($_GET["user"], "email:") !== false ? substr($_GET["user"], 6) : $_GET["user"]));
        } elseif (is_numeric($_GET["user"]) || strpos($_GET["user"], "id:") !== false) {
            $_vars["FoundUser"] = crisp\plugin\admin\Phoenix::fetchByID((strpos($_GET["user"], "id:") !== false ? substr($_GET["user"], 3) : $_GET["user"]));
        } else {
            $_vars["FoundUser"] = crisp\plugin\admin\Phoenix::fetchByUsername((strpos($_GET["user"], "username:") !== false ? substr($_GET["user"], 9) : $_GET["user"]));
        }
        if (!$_vars["FoundUser"] || count($_vars["FoundUser"]) > 1) {
            if (count($_vars["FoundUser"]) > 1) {
                $_vars["Notice"] = array("Text" => $this->getTranslation("multiple_matches", count($_vars["FoundUser"])), "Type" => "danger", "Icon" => "fas fa-search");
            } else {
                $_vars["Notice"] = array("Text" => $this->getTranslation("user_not_found"), "Type" => "danger", "Icon" => "fas fa-search");
            }
        } else {

            $_vars["FoundUser"] = $_vars["FoundUser"][0];
            $_vars["CaseComments"] = crisp\plugin\admin\Phoenix::fetchCaseCommentsByUser($_vars["FoundUser"]["id"]);
            $_vars["PointComments"] = crisp\plugin\admin\Phoenix::fetchPointCommentsByUser($_vars["FoundUser"]["id"]);
            $_vars["DocumentComments"] = crisp\plugin\admin\Phoenix::fetchDocumentCommentsByUser($_vars["FoundUser"]["id"]);
            $_vars["ServiceComments"] = crisp\plugin\admin\Phoenix::fetchServiceCommentsByUser($_vars["FoundUser"]["id"]);
            $_vars["TopicComments"] = crisp\plugin\admin\Phoenix::fetchTopicCommentsByUser($_vars["FoundUser"]["id"]);
            if ($_vars["FoundUser"]["admin"]) {
                $_vars["Special"] = true;
                $_vars["Notice"] = array("Text" => $this->getTranslation("warning_admin_account"), "Type" => "warning", "Icon" => "fas fa-exclamation-triangle");
            } elseif ($_vars["FoundUser"]["bot"]) {
                $_vars["Special"] = true;
                $_vars["Notice"] = array("Text" => $this->getTranslation("warning_bot_account"), "Type" => "warning", "Icon" => "fas fa-exclamation-triangle");
            } elseif ($_vars["FoundUser"]["id"] == "1") {
                $_vars["Special"] = true;
                $_vars["Notice"] = array("Text" => $this->getTranslation("warning_special_account"), "Type" => "warning", "Icon" => "fas fa-exclamation-triangle");
            }
        }

        if (isset($_GET["action"])) {

            if (!$_vars["Special"]) {

                switch ($_GET["action"]) {
                    case "clear_points":
                        foreach ($_vars["PointComments"] as $Index => $Comment) {
                            $Array[] = (crisp\plugin\admin\Phoenix::addSpamComment($Comment["id"], "PointComment"));
                        }
                        break;
                    case "clear_topic":
                        foreach ($_vars["TopicComments"] as $Index => $Comment) {
                            $Array[] = (crisp\plugin\admin\Phoenix::addSpamComment($Comment["id"], "TopicComment"));
                        }
                        break;
                    case "clear_case":
                        foreach ($_vars["CaseComments"] as $Index => $Comment) {
                            $Array[] = (crisp\plugin\admin\Phoenix::addSpamComment($Comment["id"], "CaseComment"));
                        }
                        break;
                    case "clear_service":
                        foreach ($_vars["ServiceComments"] as $Index => $Comment) {
                            if (!crisp\plugin\admin\Phoenix::isInSpams($Comment["id"], "ServiceComment")) {
                                $Array[] = (crisp\plugin\admin\Phoenix::addSpamComment($Comment["id"], "ServiceComment"));
                            }
                        }
                        break;
                    case "clear_document":
                        foreach ($_vars["DocumentComments"] as $Index => $Comment) {
                            $Array[] = (crisp\plugin\admin\Phoenix::addSpamComment($Comment["id"], "DocumentComment"));
                        }
                        break;
                }

                if ($_GET["action"] != "deactivate") {
                    $_vars["Notice"] = array("Text" => $this->getTranslation("user_purged", count($Array)), "Type" => "success", "Icon" => "fas fa-check");
                } else {
                    $PhoenixUser = new crisp\plugin\admin\PhoenixUser($_vars["FoundUser"]["id"]);
                    if ($PhoenixUser->deactivate()) {
                        $_vars["Notice"] = array("Text" => $this->getTranslation("user_deactivated"), "Type" => "success", "Icon" => "fas fa-check");
                        $_vars["FoundUser"] = $PhoenixUser->fetch();
                    } else {
                        $_vars["Notice"] = array("Text" => $this->getTranslation("failed_user_deactivated"), "Type" => "danger", "Icon" => "fas fa-times-o");
                    }
                }
            }
            $_vars["committed"] = $Array;
        }
    }
    $_SESSION[crisp\core\Config::$Cookie_Prefix . "_csrf"] = crisp\core\Crypto::UUIDv4("csrf_");
}
$_vars["csrf"] = $_SESSION[crisp\core\Config::$Cookie_Prefix . "_csrf"];
