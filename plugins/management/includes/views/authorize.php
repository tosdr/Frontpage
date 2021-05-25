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

use crisp\api\Config;
use crisp\api\Helper;
use crisp\core\APIPermissions;
use crisp\core\MySQL;
use crisp\core\OAuth;
use crisp\core\Security;

if(!defined('CRISP_COMPONENT')){
    echo 'Cannot access this component directly!';
    exit;
}

$server = OAuth::createServer();


$OAuthResponse = new OAuth2\Response();
$OAuthRequest = OAuth2\Request::createFromGlobals();

if (!isset($_SESSION[\crisp\core\Config::$Cookie_Prefix . 'session_login'])) {
    header('Location: ' . Config::get('root_url') . '/login?redirect_uri=' . urlencode(Helper::currentURL()));
    exit;
}

if (!$User->isSessionValid()) {
    header('Location: ' . Config::get('root_url') . '/login?redirect_uri=' . urlencode(Helper::currentURL()));
    exit;
}

if (!$server->validateAuthorizeRequest($OAuthRequest, $OAuthResponse)) {
    $OAuthResponse->send();
    exit;
}

if (!empty($_POST)) {
    if(!Security::matchCSRF($_POST['csrf'])){
        header('Location: ' . Helper::currentURL());
        exit;
    }
    $server->handleAuthorizeRequest($OAuthRequest, $OAuthResponse, isset($_POST['authorize']) && $_POST['authorize'] === 'true', $User->UserID);
    $OAuthResponse->send();
    exit;
}

$CrispDBClass = new MySQL();

$Query = $CrispDBClass->getDBConnector()->prepare('SELECT * FROM oauth_clients WHERE client_id = :client_id');

$Query->execute([':client_id' => $OAuthRequest->request('client_id', $OAuthRequest->query('client_id'))]);
$Client = $Query->fetch(PDO::FETCH_ASSOC);


$Client['permissions'] = APIPermissions::getBitmask($server->getScopeUtil()->getScopeFromRequest($OAuthRequest) ?? $server->getScopeUtil()->getDefaultScope($OAuthRequest->request('client_id', $OAuthRequest->query('client_id'))));

$_vars = ['User' => $User->fetch(), 'client' => $Client];