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

$Env = parse_ini_file(__DIR__ . "/../../.env");
session_start();


$discordProvider = new \Wohali\OAuth2\Client\Provider\Discord([
    'clientId' => $Env["DISCORD_CLIENT_ID"],
    'clientSecret' => $Env["DISCORD_CLIENT_SECRET"],
    'redirectUri' => $Env["DISCORD"]
        ]);
if (isset($_GET["sync"])) {
    $authUrl = $discordProvider->getAuthorizationUrl(['scope' => ['identify', 'email', 'guilds']]);
    $_SESSION['oauth2state'] = $discordProvider->getState();
    header('Location: ' . $authUrl);
    return;
} elseif (isset($_GET["code"]) && (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state']))) {

    unset($_SESSION['oauth2state']);
    $_vars = array("Notice" => array("Type" => "danger", "Text" => $this->getTranslation("views.sync_discord.sync.invalid_state")));
} elseif (isset($_GET["code"])) {

    try {
        $token = $discordProvider->getAccessToken('authorization_code', [
            'code' => $_GET['code']
        ]);

        try {

            $user = $discordProvider->getResourceOwner($token);
            unset($_SESSION['oauth2state']);


            $Discourse = new \pnoeric\DiscourseAPI($Env["DISCOURSE_HOSTNAME"], $Env["DISCOURSE_API_KEY"]);

            if (!$user->toArray()["verified"]) {
                $_vars = array("Notice" => array("Type" => "danger", "Text" => $this->getTranslation("views.sync_discord.sync.email_not_verified")));
            } else {

                //var_dump($Discourse->getUserByEmail($user->toArray()["email"]));
            }
        } catch (Exception $e) {

            unset($_SESSION['oauth2state']);
        }
    } catch (Exception $ex) {
        unset($_SESSION['oauth2state']);
        $_vars = array("Notice" => array("Type" => "danger", "Text" => $this->getTranslation("views.sync_discord.sync.invalid_code")));
    }
}