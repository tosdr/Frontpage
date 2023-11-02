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

if(!defined('CRISP_COMPONENT')){
    echo 'Cannot access this component directly!';
    exit;
}

$OutputType = 'html';
switch (explode('.', $GLOBALS['route']->Page)[1]) {
    case 'xml':
        header('Content-Type: application/xml; charset=utf-8');
        $OutputType = 'xml';


        include __DIR__ . '/sitemap/generate.php';




        break;
    case 'html':
    default:

        $OutputType = 'html';
}

$_vars = ['output' => $OutputType];
