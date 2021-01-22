<?php

/*
 * Copyright (C) 2020 Justin Back <jback@pixelcatproductions.net>
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

/**
 * Some useful helper functions
 */
class Helper {

  /**
   * Check if the user is on a mobile device
   * @return boolean TRUE if the user is on mobile
   */
  public static function isMobile($UserAgent = null) {
    $UserAgent = ($UserAgent === null ? $_SERVER["HTTP_USER_AGENT"] : $UserAgent);
    return preg_match("/(android|avantgo|blackberry|bolt|boost|cricket|docomo|fone|hiptop|mini|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i", $UserAgent);
  }

  public static function getAPIKey() {
    if (isset(apache_request_headers()["Api-Key"])) {
      return apache_request_headers()["Api-Key"];
    } elseif (apache_request_headers()["X-Api-Key"]) {
      return apache_request_headers()["X-Api-Key"];
    } elseif (isset($_POST["apikey"])) {
      return $_POST["apikey"];
    } elseif (isset($_GET["apikey"])) {
      return $_GET["apikey"];
    } else {
      return false;
    }
  }

  /**
   * Gets the real ip address even behind a proxy
   * @return String containing the IP of the user
   */
  public static function getRealIpAddr() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {   //check ip from share internet
      $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {   //to check ip is pass from proxy
      $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
      $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
  }

  /**
   * Get the current locale a user has set
   * @return string current letter code 
   */
  public static function getLocale() {
    $Locale = locale_accept_from_http($_SERVER['HTTP_ACCEPT_LANGUAGE']);
    if (isset($GLOBALS["route"]->Language)) {
      $Locale = $GLOBALS["route"]->Language;
    } else {
      $Locale = "en";
    }


    if (!in_array($Locale, array_keys(array_column(\crisp\api\lists\Languages::fetchLanguages(false), null, "Code")))) {
      $Locale = "en";
    }

    if (isset($_COOKIE[\crisp\core\Config::$Cookie_Prefix . "language"]) && !isset($GLOBALS["route"]->Language)) {
      $Locale = $_COOKIE[\crisp\core\Config::$Cookie_Prefix . "language"];
    }
    return $Locale;
  }

  /**
   * Sets the locale and saves in a cookie
   * 
   * @return bool
   */
  public static function setLocale() {
    return setcookie(\crisp\core\Config::$Cookie_Prefix . "language", self::getLocale(), time() + (86400 * 30), "/");
  }

  /**
   * Get the current revision the CMS runs on
   * @return string Current Git Revision
   */
  public static function getGitRevision() {
    return file_get_contents(__DIR__ . '/../../../../.git/refs/heads/' . self::getGitBranch());
  }

  /**
   * Filter a string and remove non-alphanumeric and spaces
   * @param string $String The string to filter
   * @return string Filtered string
   */
  public static function filterAlphaNum($String) {
    return str_replace(" ", "-", strtolower(preg_replace("/[^0-9a-zA-Z\-_]/", "-", $String)));
  }

  /**
   * Generate a placeholder image
   * @param string $Text The text to display
   * @param string $Size The in pixels to create the image with
   */
  public static function PlaceHolder(string $Text, string $Size = "150x150") {

    $fontSize = 5;
    $dimensions = explode('x', $Size);

    $w = isset($dimensions[0]) ? $dimensions[0] : 100;
    $h = isset($dimensions[1]) ? $dimensions[1] : 100;
    $text = isset($Text) ? $Text : $w . 'x' . $h;

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
  public static function isValidPluginName(string $Name) {

    $Matches = [];

    if (preg_match_all("/[^0-9a-zA-Z\-_]/", $Name) > 0) {
      $Matches[] = "STRING_CONTAINS_NON_ALPHA_NUM";
    }
    if (strpos($Name, ' ') !== false) {
      $Matches[] = "STRING_CONTAINS_SPACES";
    }
    if (preg_match('/[A-Z]/', $Name)) {
      $Matches[] = "STRING_CONTAINS_UPPERCASE";
    }

    return (count($Matches) > 0 ? $Matches : true);
  }

  /**
   * Get the current branch the CMS runs on
   * @return string Current Git Revision
   */
  public static function getGitBranch() {
    return trim(substr(file_get_contents(__DIR__ . '/../../../../.git/HEAD'), 16));
  }

  /**
   * Gets a current revision link to github
   * @return string The link to github
   */
  public static function getGitRevisionLink() {
    return "https://github.com/JustinBack/CrispCMS-ToS-DR/tree/" . self::getGitRevision();
  }

  public static function generateLink($Path, $External = false) {
    return ($External ? $Path : "/" . self::getLocale() . "/$Path");
  }

  public static function processRoute($Route) {

    $_Route = explode("/", $Route);
    $obj = new \stdClass();
    $obj->Language = (lists\Languages::languageExists($_Route[0]) && strlen($_Route[0]) > 0 ? $_Route[0] : self::getLocale());
    $obj->Page = (strlen($_Route[1]) === 0 ? (strlen($_Route[0]) > 0 ? $_Route[0] : false) : $_Route[1]);
    $obj->GET = array();
    if (strlen($_Route[2]) > 0) {
      $_RouteArray = $_Route;
      array_shift($_RouteArray);
      array_shift($_RouteArray);
      for ($i = 0; $i <= count($_RouteArray); $i = $i + 2) {
        $key = $_RouteArray[$i];
        $value = $_RouteArray[$i + 1];
        if (strlen($key) > 0) {
          if ($value === null) {
            $obj->GET["q"] = $key;
          } else {
            $obj->GET[$key] = $value;
          }
        }
      }
    }

    return $obj;
  }

  /**
   * Retrieve the latest hash on github
   * @param \boolean $Force Force update rather than from cache
   * @return string Hash of latest git revision
   * @throws \Exception If request failed
   */
  public static function getLatestGitRevision(bool $Force = false) {

    $EnvFile = parse_ini_file(__DIR__ . "/../../../../.env");
    if (!$Force) {

      $Timestamp = Config::getTimestamp("github_current_revision");

      if (strtotime($Timestamp["last_changed"]) >= strtotime("-15 minutes") && Config::exists("github_current_revision")) {
        return Config::get("github_current_revision");
      }
      if (strtotime($Timestamp["created_at"]) >= strtotime("-15 minutes") && Config::exists("github_current_revision")) {
        return Config::get("github_current_revision");
      }
    }


    $curl = \curl_init();
    \curl_setopt_array($curl, array(
        CURLOPT_URL => "https://api.github.com/repos/JustinBack/CrispCMS-ToS-DR/git/ref/heads/" . self::getGitRevision(),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_USERAGENT => "LophotenCMS Git Checker",
        CURLOPT_HTTPHEADER => array(
            "Authorization: Bearer " . $EnvFile["GITHUB_TOKEN"],
        ),
    ));
    $response = json_decode(\curl_exec($curl));
    if ($response->error) {
      throw new \Exception($response->message);
    }

    if (Config::exists("github_current_revision")) {
      Config::set("github_current_revision", $response->object->sha);
    } else {
      Config::create("github_current_revision", $response->object->sha);
    }
    return $response->object->sha;
  }

  /**
   * Similiar to JS startsWith, check if a text starts with a specific string
   * @param string $haystack The string to perform the check on
   * @param string $needle A search needle to search for
   * @return boolean TRUE $haystack contains $needle
   */
  public static function startsWith(string $haystack, string $needle) {
    $length = strlen($needle);
    return (substr($haystack, 0, $length) === $needle);
  }

  /**
   * Just a pretty print for var_dump
   * @param string pretty var_dump
   */
  public static function prettyDump($var) {
    echo "<pre>" . var_export($var, true) . "</pre>";
  }

  /**
   * Check if a Template exists within a specific theme
   * @param string $Theme The theme to search with
   * @param string $Template The Template name
   * @return boolean
   */
  public static function templateExists(string $Theme, string $Template) {
    return file_exists(__DIR__ . "/../../../../themes/$Theme/templates/$Template");
  }

  /**
   * Similiar to JS endsWith, check if a text ends with a specific string
   * @param type $haystack The string to perform the check on
   * @param type $needle A search needle to search for
   * @return boolean
   */
  public static function endsWith(string $haystack, string $needle) {
    $length = strlen($needle);
    if ($length == 0) {
      return true;
    }

    return (substr($haystack, -$length) === $needle);
  }

  /**
   * Truncates a text and appends "..." to the end
   * @param string $String The text to truncate
   * @param int $Length After how many chars should we truncate the text?
   * @param bool $AppendDots Should we append dots to the end of the string?
   * @return string
   */
  public static function truncateText(string $String, int $Length, bool $AppendDots = true) {
    return strlen($String) > $Length ? substr($String, 0, $Length) . ($AppendDots ? "..." : "") : $String;
  }

  /**
   * Check if a string is serialized
   * @see https://core.trac.wordpress.org/browser/tags/5.4/src/wp-includes/functions.php#L611
   * @param type $data The Data to check
   * @param type $strict Strict Checking
   * @return boolean
   */
  public static function isSerialized($data, $strict = true) {
    // if it isn't a string, it isn't serialized.
    if (!is_string($data)) {
      return false;
    }
    $data = trim($data);
    if ('N;' == $data) {
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
      if (false === $semicolon && false === $brace)
        return false;
      // But neither must be in the first X characters.
      if (false !== $semicolon && $semicolon < 3)
        return false;
      if (false !== $brace && $brace < 4)
        return false;
    }
    $token = $data[0];
    switch ($token) {
      case 's' :
        if ($strict) {
          if ('"' !== substr($data, -2, 1)) {
            return false;
          }
        } elseif (false === strpos($data, '"')) {
          return false;
        }
      // or else fall through
      case 'a' :
      case 'O' :
        return (bool) preg_match("/^{$token}:[0-9]+:/s", $data);
      case 'b' :
      case 'i' :
      case 'd' :
        $end = $strict ? '$' : '';
        return (bool) preg_match("/^{$token}:[0-9.E-]+;$end/", $data);
    }
    return false;
  }

  /**
   * Gets the current domain
   * @return string the current domain
   */
  public static function currentDomain() {
    return "https://tosdr.org";
  }

  public static function currentURL() {
    return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
  }

}
