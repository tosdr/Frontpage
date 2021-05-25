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

use crisp\core\Config;

if(!defined('CRISP_COMPONENT')){
    echo 'Cannot access this component directly!';
    exit;
}

if (!isset($_SESSION[Config::$Cookie_Prefix . 'session_login'])) {
    header('Location: ' . Config::get('root_url') . '/login?redirect_uri=' . urlencode(Helper::currentURL()));
    exit;
}

if (!$User->isSessionValid()) {
    header('Location: ' . Config::get('root_url') . '/login?redirect_uri=' . urlencode(Helper::currentURL()));
    exit;
}