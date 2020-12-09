<?php

\crisp\core\Template::addtoNavbar("frontpage", crisp\api\Translation::fetch("navbar_item_home"), "/", "_self", -99);
\crisp\core\Template::addtoNavbar("ratings", crisp\api\Translation::fetch("navbar_item_ratings"), "/#ratings", "_self", -98);
\crisp\core\Template::addtoNavbar("api", crisp\api\Translation::fetch("navbar_item_api"), "/api", "_self", -95);
\crisp\core\Template::addtoNavbar("forum", crisp\api\Translation::fetch("navbar_item_forum"), \crisp\api\Config::get("forum_url"), "_self", -94);

