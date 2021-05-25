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

namespace crisp\models;

use crisp\core\APIPermissions;
use OAuth2;
use OAuth2\RequestInterface;
use OAuth2\ResponseInterface;

if(!defined('CRISP_COMPONENT')){
    echo 'Cannot access this component directly!';
    exit;
}

class OAuth2ScopeTable implements OAuth2\Storage\ScopeInterface
{
    public static function checkScope($required_scope, OAuth2\Server $server, OAuth2\Response $response): bool
    {

        $token = $server->getAccessTokenData(OAuth2\Request::createFromGlobals());


        if (!APIPermissions::hasBitmask((int)$token['scope'], $required_scope)) {
            $response->setError(403, 'insufficient_scope', 'The request requires higher privileges than provided by the access token');
            $response->addHttpHeaders([
                'WWW-Authenticate' => sprintf('%s realm="%s", scope="%s", error="%s", error_description="%s"',
                    'Bearer',
                    'Service',
                    (int)$token['scope'],
                    $response->getParameter('error'),
                    $response->getParameter('error_description')
                )
            ]);

            return false;
        }
        return true;
    }

    public function scopeExists($scope, $client_id = null): bool
    {

        foreach(APIPermissions::getBitmask($scope) as $key => $bitmask){
            if(!str_starts_with($key, 'OAUTH_')){
                return false;
            }
        }

        return APIPermissions::bitmaskExists($scope);
    }

    public function getDefaultScope($client_id = null): int
    {
        return APIPermissions::NONE;
    }
}