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

use crisp\api\Elastic;
use crisp\core\Bitmask;
use crisp\core\PluginAPI;
use crisp\Experiments;

if (!defined('CRISP_COMPONENT')) {
    echo 'Cannot access this component directly!';
    exit;
}

$inputQuery = null;
$inputQuery = $_GET['query'] ?? $inputQuery;
$ES = new Elastic();

if (!$inputQuery) {
    PluginAPI::response(Bitmask::QUERY_FAILED + Bitmask::VERSION_DEPRECATED, 'Missing query', [
        'services' => [],
        'grid' => null
    ]);
    exit;
}

try {
    $services = $ES->search($inputQuery);

    if(Experiments::isActive(Experiments::FRONTPAGE_REDESIGN_2021_07)){

        PluginAPI::response(Bitmask::REQUEST_SUCCESS + Bitmask::VERSION_DEPRECATED, $inputQuery, [
            'services' => $services,
            'grid' => $this->TwigTheme->render('_experiments/FRONTPAGE_REDESIGN_2021_07/components/servicegrid/grid.twig', ['Services' => $services->hits, 'columns' => 2])
        ]);
        exit;
    }

    PluginAPI::response(Bitmask::REQUEST_SUCCESS + Bitmask::VERSION_DEPRECATED, $inputQuery, [
        'services' => $services,
        'grid' => $this->TwigTheme->render('_prod/components/servicegrid/grid.twig', ['Services' => $services->hits, 'columns' => 2])
    ]);

} catch (Throwable $e) {
    PluginAPI::response(Bitmask::QUERY_FAILED + Bitmask::VERSION_DEPRECATED, 'Internal Server Error', [
        'services' => [],
        'grid' => []
    ]);
}