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

namespace crisp\api;

use crisp\api\lists\Languages;
use crisp\core\MySQL;
use Exception;
use PDO;
use stdClass;
use Stevenmaguire\OAuth2\Client\Provider\Keycloak;

/**
 * Some useful helper functions
 */
class Helper
{

    /**
     * Check if the user is on a mobile device
     * @return boolean TRUE if the user is on mobile
     */
    public static function isMobile($userAgent = null): bool
    {
        $userAgent = ($userAgent ?? $_SERVER['HTTP_USER_AGENT']);
        return preg_match('/(android|avantgo|blackberry|bolt|boost|cricket|docomo|fone|hiptop|mini|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i', $userAgent);
    }


    public static function authorizeAction(string $Action, string $RequiredPermission = null)
    {
        if (!isset($_SESSION['authorizeActionUrl_' . $Action])) {
            $_SESSION['authorizeActionUrl_' . $Action] = self::currentURL();
        }
        $EnvFile = parse_ini_file(__DIR__ . '/../../../../.env');

        $provider = new Keycloak([
            'authServerUrl' => $EnvFile['KEYCLOAK_URL'],
            'realm' => $EnvFile['KEYCLOAK_REALM'],
            'clientId' => $EnvFile['KEYCLOAK_ID'],
            'clientSecret' => $EnvFile['KEYCLOAK_SECRET'],
            'redirectUri' => $_SESSION['authorizeActionUrl_' . $Action],
        ]);

        if (!isset($_GET['code'])) {
            $authUrl = $provider->getAuthorizationUrl();
            $_SESSION['authorizeActionstate_' . $Action] = $provider->getState();

            header('Location: ' . $authUrl);
            exit;
        }


        if (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['authorizeActionstate_' . $Action])) {
            unset($_SESSION['authorizeActionstate_' . $Action], $_SESSION['authorizeActionUrl_' . $Action]);
            return false;
        }


        try {
            $token = $provider->getAccessToken('authorization_code', [
                'code' => $_GET['code']
            ]);
            unset($_SESSION['authorizeActionstate_' . $Action], $_SESSION['authorizeActionUrl_' . $Action]);
            if ($RequiredPermission !== null) {
                $user = $provider->getResourceOwner($token)->toArray();

                return !(!in_array($RequiredPermission, $user['permissions'], true) || !$user['email_verified']);
            }
            return true;
        } catch (Exception) {
            unset($_SESSION['authorizeActionstate_' . $Action], $_SESSION['authorizeActionUrl_' . $Action]);
            return false;
        }

    }


    /**
     * @param $BitmaskFlag
     * @param null $apikey
     * @return bool|int
     */
    public static function hasApiPermissions($BitmaskFlag, $apikey = null): bool|int
    {

        if ($apikey === null) {
            $apikey = self::getApiKey();
        }

        $keyDetails = self::getAPIKeyDetails($apikey);

        if (!$keyDetails) {
            return false;
        }


        return ($keyDetails['permissions'] & $BitmaskFlag);
    }

    public static function hasApiHeaders(): bool
    {
        if (isset($_SERVER['HTTP_X_API_KEY'])) {
            return true;
        }

        if (isset($_SERVER['HTTP_AUTHORIZATION']) && !str_starts_with($_SERVER['HTTP_AUTHORIZATION'], 'Bearer ')) {
            return true;
        }
        return false;
    }


    /**
     * @param string $ApiKey
     * @return mixed
     */
    public static function getAPIKeyDetails(string $ApiKey): mixed
    {


        $Postgres = new MySQL();

        $statement = $Postgres->getDBConnector()->prepare('SELECT * FROM apikeys WHERE key = :key');

        $statement->execute([':key' => $ApiKey]);

        if ($statement->rowCount() > 0) {
            return $statement->fetch(PDO::FETCH_ASSOC);
        }
        return false;
    }

    /**
     * @param string|null $apikey
     * @return bool
     */
    public static function getApiKey(): string
    {

        if (isset($_SERVER['HTTP_X_API_KEY'])) {
            return $_SERVER['HTTP_X_API_KEY'];
        } else if (isset($_SERVER['HTTP_AUTHORIZATION']) && !str_starts_with($_SERVER['HTTP_AUTHORIZATION'], 'Bearer ')) {
            return $_SERVER['HTTP_AUTHORIZATION'];
        }
        return false;
    }

    /**
     * @param string|null $apikey
     * @return bool
     */
    public static function isApiKeyValid(string $apikey = null): bool
    {

        if ($apikey === null) {
            return false;
        }

        $Postgres = new MySQL();

        $statement = $Postgres->getDBConnector()->prepare('SELECT * FROM apikeys WHERE key = :key AND revoked = 0 AND (expires_at is null OR expires_at > NOW())');

        $statement->execute([':key' => $apikey]);

        return $statement->rowCount() > 0;
    }

    public static function getCommitHash(): ?string {
        return trim(exec('git log --pretty="%h" -n1 HEAD'));
    }

    /**
     * Gets the real ip address even behind a proxy
     * @return String containing the IP of the user
     */
    public static function getRealIpAddr(): string
    {
        if (!empty($_SERVER['HTTP_X_REAL_IP'])) {
            return $_SERVER['HTTP_X_REAL_IP'];
        }

        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {   //to check ip is pass from proxy
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        }

        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {   //check ip from share internet
            return $_SERVER['HTTP_CLIENT_IP'];
        }

        return $_SERVER['REMOTE_ADDR'];
    }

    /**
     * Get the current locale a user has set
     * @return string current letter code
     */
    public static function getLocale(): string
    {
        $Locale = $GLOBALS['route']->Language ?? locale_accept_from_http($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'en');


        if (!array_key_exists($Locale, array_column(Languages::fetchLanguages(false), null, 'code'))) {
            $Locale = 'en';
        }

        if (isset($_COOKIE[\crisp\core\Config::$Cookie_Prefix . 'language']) && !isset($GLOBALS['route']->Language)) {
            $Locale = $_COOKIE[\crisp\core\Config::$Cookie_Prefix . 'language'];
        }
        return $Locale;
    }

    /**
     * Sets the locale and saves in a cookie
     *
     * @return bool
     */
    public static function setLocale(): bool
    {
        return setcookie(\crisp\core\Config::$Cookie_Prefix . 'language', self::getLocale(), time() + (86400 * 30), '/');
    }

    /**
     * Filter a string and remove non-alphanumeric and spaces
     * @param string $String The string to filter
     * @return string Filtered string
     */
    public static function filterAlphaNum(string $String, bool $LowerCaseOnly = true): string
    {
        $Raw = str_replace(' ', '-', preg_replace('/[^0-9a-zA-Z\-_]/', '-', $String));
        if ($LowerCaseOnly) {
            return strtolower($Raw);
        }
        return $Raw;
    }

    /**
     * Generate a placeholder image
     * @param string $Text The text to display
     * @param string $Size The in pixels to create the image with
     */
    public static function PlaceHolder(string $Text, string $Size = '150x150')
    {

        $fontSize = 5;
        $dimensions = explode('x', $Size);

        $w = $dimensions[0] ?? 100;
        $h = $dimensions[1] ?? 100;
        $text = $Text ?? $w . 'x' . $h;

        if ($w < 50) {
            $fontSize = 1;
        }

        $im = imagecreatetruecolor($w, $h);
        $bg = imagecolorallocate($im, 204, 204, 204);

        imagefilledrectangle($im, 0, 0, $w, $h, $bg);

        $fontWidth = imagefontwidth($fontSize);
        $textWidth = $fontWidth * strlen($text);
        $textLeft = ceil(($w - $textWidth) / 2);

        $fontHeight = imagefontheight($fontSize);
        $textHeight = $fontHeight;
        $textTop = ceil(($h - $textHeight) / 2);

        imagestring($im, $fontSize, $textLeft, $textTop, $text, 0x969696);

        header('Content-Type: image/jpg');

        imagegif($im);
        imagedestroy($im);
    }

    /**
     * Validates if the plugin name
     * @param string $Name The name of the plugin
     * @return array|boolean Array of errors if found, otherwise true
     */
    public static function isValidPluginName(string $Name): bool|array
    {

        $Matches = [];

        if (preg_match_all('/[^0-9a-zA-Z\-_]/', $Name) > 0) {
            $Matches[] = 'STRING_CONTAINS_NON_ALPHA_NUM';
        }
        if (str_contains($Name, ' ')) {
            $Matches[] = 'STRING_CONTAINS_SPACES';
        }
        if (preg_match('/[A-Z]/', $Name)) {
            $Matches[] = 'STRING_CONTAINS_UPPERCASE';
        }

        return (count($Matches) > 0 ? $Matches : true);
    }

    /**
     * @param string $Path
     * @param false $External
     * @return string
     */
    public static function generateLink(string $Path, bool $External = false): string
    {
        return ($External ? $Path : '/' . self::getLocale() . "/$Path");
    }

    /**
     * @param $Route
     * @return stdClass
     */
    public static function processRoute($Route): stdClass
    {
        $_Route = explode('/', $Route);
        array_shift($_Route);
        if (isset($_SERVER['IS_API_ENDPOINT'])) {
            array_unshift($_Route, 'api');
        }
        $obj = new stdClass();
        $obj->Language = (lists\Languages::languageExists($_Route[0]) && strlen($_Route[0]) > 0 ? $_Route[0] : self::getLocale());
        $obj->Page = explode('?', (!isset($_Route[1]) || strlen($_Route[1]) === 0 ? (strlen($_Route[0]) > 0 ? $_Route[0] : false) : $_Route[1]))[0];
        $obj->GET = [];
        if (isset($_Route[2]) && $_Route[2] !== '') {
            $_RouteArray = $_Route;
            array_shift($_RouteArray);
            array_shift($_RouteArray);
            for ($i = 0, $iMax = count($_RouteArray); $i <= $iMax; $i += 2) {
                $key = $_RouteArray[$i];

                $value = $_RouteArray[$i + 1] ?? null;

                if ($key !== '') {
                    if ($value === null) {
                        $obj->GET['q'] = explode('?', $key)[0];
                    } else {
                        $obj->GET[$key] = explode('?', $value)[0];
                    }
                }
            }
        }
        if (str_contains($Route, '?')) {
            $qexplode = explode('?', $Route);
            array_shift($qexplode);
            foreach ($qexplode as $key) {
                $key = explode('=', $key);
                $_GET[$key[0]] = $key[1];
            }
        }
        return $obj;
    }

    /**
     * Just a pretty print for var_dump
     * @param string pretty var_dump
     */
    public static function prettyDump($var): void
    {
        echo sprintf('<pre>%s</pre>', var_export($var, true));
    }

    /**
     * Check if a Template exists within a specific theme
     * @param string $Theme The theme to search with
     * @param string $Template The Template name
     * @return boolean
     */
    public static function templateExists(string $Theme, string $Template): bool
    {
        return file_exists(__DIR__ . "/../../../../themes/$Theme/templates/$Template");
    }

    /**
     * Truncates a text and appends "..." to the end
     * @param string $String The text to truncate
     * @param int $Length After how many chars should we truncate the text?
     * @param bool $AppendDots Should we append dots to the end of the string?
     * @return string
     */
    public static function truncateText(string $String, int $Length, bool $AppendDots = true): string
    {
        return strlen($String) > $Length ? substr($String, 0, $Length) . ($AppendDots ? '...' : '') : $String;
    }


    /**
     * @param string $Text
     * @param bool $NewLine
     */
    public static function writeConsole(string $Text, bool $NewLine = true): void
    {
        if (PHP_SAPI === 'cli') {
            echo $Text . ($NewLine ? PHP_EOL : '');
        }
    }

    /**
     * Check if a string is serialized
     * @see https://core.trac.wordpress.org/browser/tags/5.4/src/wp-includes/functions.php#L611
     * @param string $data The Data to check
     * @param bool $strict Strict Checking
     * @return boolean
     */
    public static function isSerialized(string $data, bool $strict = true): bool
    {
        // if it isn't a string, it isn't serialized.
        if (!is_string($data)) {
            return false;
        }
        $data = trim($data);
        if ('N;' === $data) {
            return true;
        }
        if (strlen($data) < 4) {
            return false;
        }
        if (':' !== $data[1]) {
            return false;
        }
        if ($strict) {
            $lastc = substr($data, -1);
            if (';' !== $lastc && '}' !== $lastc) {
                return false;
            }
        } else {
            $semicolon = strpos($data, ';');
            $brace = strpos($data, '}');
            // Either ; or } must exist.
            if (false === $semicolon && false === $brace) {
                return false;
            }
            // But neither must be in the first X characters.
            if (false !== $semicolon && $semicolon < 3) {
                return false;
            }
            if (false !== $brace && $brace < 4) {
                return false;
            }
        }
        $token = $data[0];
        switch ($token) {
            case 's' :
                if ($strict) {
                    if ('"' !== substr($data, -2, 1)) {
                        return false;
                    }
                } elseif (!str_contains($data, '"')) {
                    return false;
                }
            // or else fall through
            case 'a' :
            case 'O' :
                return (bool)preg_match("/^{$token}:[0-9]+:/s", $data);
            case 'b' :
            case 'i' :
            case 'd' :
                $end = $strict ? '$' : '';
                return (bool)preg_match("/^{$token}:[0-9.E-]+;$end/", $data);
        }
        return false;
    }


    /**
     * @return string
     */
    public static function currentURL(): string
    {
        return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    }

}
