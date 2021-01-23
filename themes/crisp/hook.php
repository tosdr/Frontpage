<?php

\crisp\core\Theme::addtoNavbar("frontpage", crisp\api\Translation::fetch("components.navbar.home"), \crisp\api\Helper::generateLink("frontpage"), "_self", -99);
\crisp\core\Theme::addtoNavbar("ratings", crisp\api\Translation::fetch("components.navbar.ratings"), \crisp\api\Helper::generateLink("frontpage#ratings"), "_self", -98);
\crisp\core\Theme::addtoNavbar("api", crisp\api\Translation::fetch("components.navbar.api"), \crisp\api\Helper::generateLink("api"), "_self", -95);
\crisp\core\Theme::addtoNavbar("forum", crisp\api\Translation::fetch("components.navbar.forum"), \crisp\api\Helper::generateLink(\crisp\api\Config::get("forum_url"), true), "_self", -94);
\crisp\core\Theme::addtoNavbar("presskit", crisp\api\Translation::fetch("components.navbar.presskit"), \crisp\api\Helper::generateLink("presskit"), "_self", -93);
\crisp\core\Theme::addtoNavbar("status", crisp\api\Translation::fetch("components.navbar.status"), \crisp\api\Helper::generateLink(\crisp\api\Config::get("status_url"), true), "_self", -92);
\crisp\core\Theme::addtoNavbar("donate", crisp\api\Translation::fetch("components.navbar.donate"), \crisp\api\Helper::generateLink(\crisp\api\Config::get("opencollective_url"), true), "_self", 0, "right");
\crisp\core\Theme::addtoNavbar("about", crisp\api\Translation::fetch("components.navbar.about"), \crisp\api\Helper::generateLink("about"), "_self", -97);
\crisp\core\Theme::addtoNavbar("downloads", crisp\api\Translation::fetch("components.navbar.download"), \crisp\api\Helper::generateLink("downloads"), "_self", -96);

if (\crisp\api\Config::get("maintenance_enabled") || isset($_GET["simulate_maintenance"])) {
  http_response_code(503);
  echo $TwigTheme->render("/templates/errors/maintenance.twig", array("plugin" => $this));
  exit;
}
if (\crisp\api\Config::get("highload_enabled") || isset($_GET["simulate_highload"])) {
  http_response_code(503);
  echo $TwigTheme->render("/templates/errors/highload.twig", array("plugin" => $this));
  exit;
}