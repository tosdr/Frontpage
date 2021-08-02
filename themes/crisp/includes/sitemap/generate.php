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

$urlset = '<url>
    <loc>{{ loc }}</loc>
    <changefreq>always</changefreq>
</url>
';

echo '<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
';

foreach (Phoenix::getServices() as $Service) {
    echo strtr($urlset, ['{{ loc }}' => crisp\api\Config::get('root_url')."/en/service/$Service[id]"]);
    echo strtr($urlset, ['{{ loc }}' => crisp\api\Config::get('root_url')."/en/embed/$Service[id]"]);
}

/* Legal Page */
echo strtr($urlset, ['{{ loc }}' => crisp\api\Config::get('root_url'). '/legal']);

/* Bitmask Page */
echo strtr($urlset, ['{{ loc }}' => crisp\api\Config::get('root_url'). '/bitmask']);

/* About Page */
echo strtr($urlset, ['{{ loc }}' => crisp\api\Config::get('root_url'). '/about']);

/* API Page */
echo strtr($urlset, ['{{ loc }}' => crisp\api\Config::get('root_url'). '/api']);

/* Classification Page */
echo strtr($urlset, ['{{ loc }}' => crisp\api\Config::get('root_url'). '/classification']);

/* Downloads Page */
echo strtr($urlset, ['{{ loc }}' => crisp\api\Config::get('root_url'). '/downloads']);

/* Frontpage Page */
echo strtr($urlset, ['{{ loc }}' => crisp\api\Config::get('root_url'). '/frontpage']);

/* Imprint Page */
echo strtr($urlset, ['{{ loc }}' => crisp\api\Config::get('root_url'). '/imprint']);

/* New Service Page */
echo strtr($urlset, ['{{ loc }}' => crisp\api\Config::get('root_url'). '/new_service']);

/* Presskit Page */
echo strtr($urlset, ['{{ loc }}' => crisp\api\Config::get('root_url'). '/presskit']);

/* Thanks Page */
echo strtr($urlset, ['{{ loc }}' => crisp\api\Config::get('root_url'). '/thanks']);

/* tosdr.txt Page */
echo strtr($urlset, ['{{ loc }}' => crisp\api\Config::get('root_url'). '/txt']);

echo '</urlset>';
