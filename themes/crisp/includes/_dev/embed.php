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

use crisp\api\Phoenix;

if (!defined('CRISP_COMPONENT')) {
    echo 'Cannot access this component directly!';
    exit;
}

if (!isset($GLOBALS['route']->GET['q'])) {
    header('Location: /');
    exit;
}

$max = (isset($_GET['max']) && is_numeric($_GET['max']) && $_GET['max'] >= 7 && $_GET['max'] <= 50 ? $_GET['max'] : null);

try {
    if (is_numeric($GLOBALS['route']->GET['q'])) {
        $_vars = ['service' => Phoenix::getService($GLOBALS['route']->GET['q']), 'max' => $max, 'embed' => true];
    } else {
        $_vars = ['service' => Phoenix::getServiceByName(urldecode($GLOBALS['route']->GET['q'])), 'max' => $max, 'embed' => true];
    }
} catch (Exception $ex) {
    header('Location: /');
    exit;
}