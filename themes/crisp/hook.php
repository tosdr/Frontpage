<?php

\crisp\core\Theme::addtoNavbar("frontpage", crisp\api\Translation::fetch("navbar_item_home"), \crisp\api\Helper::generateLink("frontpage"), "_self", -99);
\crisp\core\Theme::addtoNavbar("ratings", crisp\api\Translation::fetch("navbar_item_ratings"), \crisp\api\Helper::generateLink("#ratings"), "_self", -98);
\crisp\core\Theme::addtoNavbar("api", crisp\api\Translation::fetch("navbar_item_api"), \crisp\api\Helper::generateLink("api"), "_self", -95);
\crisp\core\Theme::addtoNavbar("forum", crisp\api\Translation::fetch("navbar_item_forum"), \crisp\api\Helper::generateLink(\crisp\api\Config::get("forum_url"), true), "_self", -94);
\crisp\core\Theme::addtoNavbar("presskit", crisp\api\Translation::fetch("presskit"), \crisp\api\Helper::generateLink("presskit"), "_self", -93);
\crisp\core\Theme::addtoNavbar("status", crisp\api\Translation::fetch("status"), \crisp\api\Helper::generateLink(\crisp\api\Config::get("status_url"), true), "_self", -92);
\crisp\core\Theme::addtoNavbar("donate", crisp\api\Translation::fetch("donate"), \crisp\api\Helper::generateLink(\crisp\api\Config::get("opencollective_url"), true), "_self", 0, "right");
