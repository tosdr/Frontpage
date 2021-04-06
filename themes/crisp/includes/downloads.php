<?php

/* 
 * Copyright (C) 2021 Justin René Back <justin@tosdr.org>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

$FeaturedExtension;

$_vars["extensions"] = \crisp\api\Config::get("extensions");

if (CURRENT_UNIVERSE >= crisp\Universe::UNIVERSE_PUBLIC) {
  foreach ($_vars["extensions"] as $Key => $Extension) {
    if (strpos($Extension->browser, get_browser(null, true)["browser"]) !== false) {
      $FeaturedExtension = $Extension;
      unset($_vars["extensions"][$Key]);
    }
  }
}

$_vars["featured"] = $FeaturedExtension;
