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

use crisp\core\PluginAPI;

if(!defined('CRISP_COMPONENT')){
    echo 'Cannot access this component directly!';
    exit;
}


if(!IS_NATIVE_API){
    PluginAPI::response(crisp\core\Bitmask::GENERIC_ERROR, 'Cannot access non-native API endpoint', [], null, 400);
    exit;
}

$exclude = explode(',', $GLOBALS['route']->GET['exclude']);

array_shift($exclude);

$_all = crisp\api\Phoenix::getServices();

$Content = '';
$isExcluded = false;

foreach ($_all as $Service) {
    if ($Service['is_comprehensively_reviewed'] && $Service['rating'] === 'E') {
        $isExcluded = false;
        if (count($exclude) > 0) {
            if (in_array($Service['name'], $exclude, true)) {
                $isExcluded = true;
            } else if (in_array($Service['id'], $exclude, true)) {
                $isExcluded = true;
            } else if ((!$Service['slug'] || $Service['slug'] !== '') && in_array($Service['slug'], $exclude, true)) {
                $isExcluded = true;
            }
        }
        $Content .= '#### ' . $Service['name'] . " ####\n";
        ($isExcluded ? $Content .= '# WARNING: ' . $Service['name'] . " HAS BEEN EXCLUDED\n" : null);
        ($Service['wikipedia'] ? $Content .= '# Wikipedia: ' . $Service['wikipedia'] . "\n" : null);
        $Content .= '# ToS;DR: ' . crisp\api\Config::get('root_url') . '/en/service/' . $Service['id'] . "\n";
        foreach (explode(',', $Service['url']) as $URL) {
            $URL = trim($URL);
            if ($URL === '') {
                continue;
            }
            $Content .= ($isExcluded ? "# 0.0.0.0 $URL\n" : "0.0.0.0 $URL\n");
        }
        $Content .= "\n\n";
    }
}

header('Content-Type: text/plain');
echo $this->TwigTheme->render('_prod/pihole.twig', [
    'content' => $Content,
    'expires' => date('M d, Y', strtotime('+7 day')),
    'version' => time(),
    'modified' => time()
]);
