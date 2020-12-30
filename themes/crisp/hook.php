<?php

\crisp\core\Theme::addtoNavbar("frontpage", crisp\api\Translation::fetch("navbar_item_home"), "/", "_self", -99);
\crisp\core\Theme::addtoNavbar("ratings", crisp\api\Translation::fetch("navbar_item_ratings"), "/#ratings", "_self", -98);
\crisp\core\Theme::addtoNavbar("api", crisp\api\Translation::fetch("navbar_item_api"), "/api", "_self", -95);
\crisp\core\Theme::addtoNavbar("forum", crisp\api\Translation::fetch("navbar_item_forum"), \crisp\api\Config::get("forum_url"), "_self", -94);
\crisp\core\Theme::addtoNavbar("presskit", crisp\api\Translation::fetch("presskit"), "/presskit", "_self", -93);
\crisp\core\Theme::addtoNavbar("donate", crisp\api\Translation::fetch("donate"), \crisp\api\Config::get("opencollective_url"), "_self", 0, "right");