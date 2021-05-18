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

$Interface = null;

if(!IS_NATIVE_API){
    PluginAPI::response(crisp\core\Bitmask::GENERIC_ERROR, "Cannot access non-native API endpoint", []);
    exit;
}

if (is_array($GLOBALS["route"]->GET)) {
    $Interface = array_key_first($GLOBALS["route"]->GET);
}

$server =\crisp\core\OAuth::createServer();

switch($GLOBALS["route"]->GET[$Interface]){
    case "authorize":

        $OAuthResponse = new OAuth2\Response();
        $OAuthRequest = OAuth2\Request::createFromGlobals();

        if (!$server->validateAuthorizeRequest($OAuthRequest, $OAuthResponse)) {
            $OAuthResponse->send();
            exit;
        }

        if (!isset($_SESSION[\crisp\core\Config::$Cookie_Prefix . "session_login"])) {
            header("Location: ". \crisp\api\Config::get("root_url"). "/login?redirect_uri=". \crisp\api\Helper::currentURL());
            exit;
        }

        if(empty($_POST) || !isset($_POST)){
            echo $this->TwigTheme->render("views/about.twig");
            exit;
        }

        $server->handleAuthorizeRequest($OAuthRequest, $OAuthResponse, isset($_POST["authorize"]));
        $OAuthResponse->send();
        exit;
    case "token":
        $server->handleTokenRequest(OAuth2\Request::createFromGlobals())->send();
        exit;
    case "revoke":
        $server->handleRevokeRequest(OAuth2\Request::createFromGlobals())->send();
        exit;
    default:
        PluginAPI::response(crisp\core\Bitmask::NOT_IMPLEMENTED, "Invalid Call", [], null, 405);
}