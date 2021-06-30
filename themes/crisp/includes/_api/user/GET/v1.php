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

use crisp\core\APIPermissions;
use crisp\core\Bitmask;
use crisp\core\PluginAPI;
use crisp\core\OAuth;
use crisp\core\Postgres;
use crisp\models\OAuth2ScopeTable;

if (!defined('CRISP_COMPONENT')) {
    echo 'Cannot access this component directly!';
    exit;
}

$server = OAuth::createServer();

$request = OAuth2\Request::createFromGlobals();
$response = new OAuth2\Response();

if (!$server->verifyResourceRequest($request, $response)) {
    $response->send();
    exit;
} else if (!OAuth2ScopeTable::checkScope(APIPermissions::OAUTH_READ_USER, $server, $response)) {
    $response->send();
    exit;
}


$token = $server->getAccessTokenData(OAuth2\Request::createFromGlobals());


if ($token['user_id'] === null) {
    PluginAPI::response(Bitmask::GENERIC_ERROR, 'EMPTY USER ID');
    exit;
}

$UserObj = [
    'id' => null,
    'username' => null,
    'is_staff' => null,
    'is_curator' => null,
    'is_bot' => null
];


$Phoenix = new Postgres();

$DB = $Phoenix->getDBConnector();

$UserQuery = $DB->prepare('SELECT * FROM users WHERE id = :id');

$UserQuery->execute([':id' => $token['user_id']]);

$User = $UserQuery->fetch(PDO::FETCH_ASSOC);

if (!$User) {
    PluginAPI::response(Bitmask::GENERIC_ERROR, 'EMPTY USER');
    exit;
}


if (OAuth2ScopeTable::checkScope(APIPermissions::OAUTH_READ_USER, $server)) {
    $UserObj['id'] = $token['user_id'];
}
if (OAuth2ScopeTable::checkScope(APIPermissions::OAUTH_CAN_SEE_USERNAME, $server)) {
    $UserObj['username'] = $User['username'];
}
if (OAuth2ScopeTable::checkScope(APIPermissions::OAUTH_CAN_SEE_STAFF_STATUS, $server)) {
    $UserObj['is_staff'] = $User['admin'];
}
if (OAuth2ScopeTable::checkScope(APIPermissions::OAUTH_CAN_SEE_CURATOR_STATUS, $server)) {
    $UserObj['is_curator'] = $User['curator'];
}
if (OAuth2ScopeTable::checkScope(APIPermissions::OAUTH_CAN_SEE_BOT_STATUS, $server)) {
    $UserObj['is_bot'] = $User['bot'];
}
header('Content-Type: application/json');
echo json_encode($UserObj, JSON_THROW_ON_ERROR);