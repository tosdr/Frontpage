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
     * Get the current revision the CMS runs on
     * @return string Current Git Revision
     */
    public static function getGitRevision() {
        return file_get_contents(__DIR__ . '/../../../../.git/refs/heads/'. self::getGitBranch());
    }

    /**
     * Get the current branch the CMS runs on
     * @return string Current Git Revision
     */
    public static function getGitBranch() {
        return trim(substr(file_get_contents(__DIR__ . '/../../../../.git/HEAD'), 16));
    }

    public static function getGitRevisionLink() {
        return "https://github.com/JustinBack/CrispCMS-ToS-DR/tree/" . self::getGitRevision();
    }

    public static function getLatestGitRevision($Force = false) {

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
            CURLOPT_URL => "https://api.github.com/repos/JustinBack/CrispCMS-ToS-DR/git/ref/heads/master",
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
     * @param type $haystack The string to perform the check on
     * @param type $needle A search needle to search for
     * @return Boolean
     */
    public static function startsWith($haystack, $needle) {
        $length = strlen($needle);
        return (substr($haystack, 0, $length) === $needle);
    }

    public static function unique_multidim_array($array, $key) {
        $temp_array = array();
        $i = 0;
        $key_array = array();

        foreach ($array as $val) {
            if (!in_array($val[$key], $key_array)) {
                $key_array[$i] = $val[$key];
                $temp_array[$i] = $val;
            }
            $i++;
        }
        return $temp_array;
    }

    public static function prettyDump($var) {
        echo "<pre>" . var_export($var, true) . "</pre>";
    }

    /**
     * Check if a Template exists within a specific theme
     * @param type $Theme The theme to search with
     * @param type $Template The Template name
     * @return Boolean
     */
    public static function templateExists($Theme, $Template) {
        return file_exists(__DIR__ . "/../../../../themes/$Theme/templates/$Template");
    }

    /**
     * Similiar to JS endsWith, check if a text ends with a specific string
     * @param type $haystack The string to perform the check on
     * @param type $needle A search needle to search for
     * @return Boolean
     */
    public static function endsWith($haystack, $needle) {
        $length = strlen($needle);
        if ($length == 0) {
            return true;
        }

        return (substr($haystack, -$length) === $needle);
    }

    /**
     * Truncates a text and appends "..." to the end
     * @param type $String The text to truncate
     * @param type $Length After how many chars should we truncate the text?
     * @return String
     */
    public static function truncateText($String, $Length, $AppendDots = true) {
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

}
