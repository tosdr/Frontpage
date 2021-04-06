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

function getLineWithString($array, $str) {
    foreach ($array as $lineNumber => $line) {
        if (strpos($line, $str) !== false) {
            return $lineNumber;
        }
    }
    return -1;
}

if (isset($_POST["domain"])) {
    if (empty($_POST["domain"])) {
        echo crisp\core\PluginAPI::response(crisp\core\Bitmask::QUERY_FAILED, \crisp\api\Translation::fetch("views.txt.errors.no_domain"));
        exit;
    }
    if (!filter_var(gethostbyname($_POST["domain"]), FILTER_VALIDATE_IP)) {
        echo crisp\core\PluginAPI::response(crisp\core\Bitmask::QUERY_FAILED, \crisp\api\Translation::fetch("views.txt.errors.invalid_domain"));
        exit;
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://" . $_POST["domain"] . "/tosdr.txt");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    $txtFile = curl_exec($ch);
    $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_status === 404) {
        echo crisp\core\PluginAPI::response(crisp\core\Bitmask::QUERY_FAILED, \crisp\api\Translation::fetch("views.txt.errors.not_found", 1, ["{{ path }}" => "https://" . $_POST["domain"] . "/tosdr.txt"]));
        exit;
    }
    if ($http_status !== 200) {
        echo crisp\core\PluginAPI::response(crisp\core\Bitmask::QUERY_FAILED, \crisp\api\Translation::fetch("views.txt.errors.non_success", 1, ["{{ path }}" => "https://" . $_POST["domain"] . "/tosdr.txt"]));
        exit;
    }
    $txtFileExploded = explode("\n", $txtFile);
    foreach ($txtFileExploded as $key => $line) {
        if (strpos($line, "Domains:") !== false) {
            continue;
        }
        if (strpos($line, "Document-Name:") !== false) {
            continue;
        }
        if (strpos($line, "Url:") !== false) {
            continue;
        }
        if (strpos($line, "Path:") !== false) {
            continue;
        }
        unset($txtFileExploded[$key]);
    }

    $domainLine = getLineWithString($txtFileExploded, "Domains:");

    if ($domainLine === -1) {
        echo crisp\core\PluginAPI::response(crisp\core\Bitmask::QUERY_FAILED, \crisp\api\Translation::fetch("views.txt.errors.invalid_line", 1, [
                    "{{ line }}" => -1,
                    "{{ expected }}" => "Domains",
                    "{{ got }}" => "Nothing"
        ]));
        exit;
    }

    $parsed = array();

    $dmarray = array();

    $domains = explode(",", explode(":", $txtFileExploded[$domainLine])[1]);

    $domainRegex = '/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z0-9][a-z0-9-]{0,61}[a-z0-9]$/';
    foreach ($domains as $domain) {

        if (empty(trim($domain))) {
            echo crisp\core\PluginAPI::response(crisp\core\Bitmask::QUERY_FAILED, \crisp\api\Translation::fetch("views.txt.errors.invalid_domain_list", 1, [
                        "{{ domain }}" => trim($domain),
            ]));
            exit;
        }

        if (preg_match($domainRegex, trim($domain))) {
            $dmarray[] = trim($domain);
            continue;
        }
        echo crisp\core\PluginAPI::response(crisp\core\Bitmask::QUERY_FAILED, \crisp\api\Translation::fetch("views.txt.errors.invalid_domain_list", 1, [
                    "{{ domain }}" => trim($domain),
        ]));
        exit;
    }

    $parsed["Domains"] = $dmarray;
    
    array_shift($txtFileExploded);


    $countDocuments = substr_count($txtFile, "Document-Name:");
    for ($i = 0; $i < $countDocuments; $i++) {

        $firstDocumentLine = getLineWithString($txtFileExploded, "Document-Name:");

        if ($firstDocumentLine === -1) {
            echo crisp\core\PluginAPI::response(crisp\core\Bitmask::QUERY_FAILED, \crisp\api\Translation::fetch("views.txt.errors.invalid_line", 1, [
                        "{{ line }}" => -1,
                        "{{ expected }}" => "Document-Name",
                        "{{ got }}" => "Nothing"
            ]));
            exit;
        }


        $documentName = explode(":", $txtFileExploded[$firstDocumentLine]);
        $documentUrl = explode(":", $txtFileExploded[$firstDocumentLine + 1]);
        $documentPath = explode(":", $txtFileExploded[$firstDocumentLine + 2]);

        array_shift($txtFileExploded);
        array_shift($txtFileExploded);
        array_shift($txtFileExploded);


        if ($documentName[0] !== "Document-Name") {
            echo crisp\core\PluginAPI::response(crisp\core\Bitmask::QUERY_FAILED, \crisp\api\Translation::fetch("views.txt.errors.invalid_line", 1, [
                        "{{ line }}" => $firstDocumentLine + 1,
                        "{{ expected }}" => "Document-Name",
                        "{{ got }}" => $documentName[0]
            ]));
            exit;
        }

        if ($documentUrl[0] !== "Url") {
            echo crisp\core\PluginAPI::response(crisp\core\Bitmask::QUERY_FAILED, \crisp\api\Translation::fetch("views.txt.errors.invalid_line", 1, [
                        "{{ line }}" => $firstDocumentLine + 2,
                        "{{ expected }}" => "Url",
                        "{{ got }}" => $documentUrl[0]
            ]));
            exit;
        }

        if ($documentPath[0] !== "Path") {
            echo crisp\core\PluginAPI::response(crisp\core\Bitmask::QUERY_FAILED, \crisp\api\Translation::fetch("views.txt.errors.invalid_line", 1, [
                        "{{ line }}" => $firstDocumentLine + 3,
                        "{{ expected }}" => "Url",
                        "{{ got }}" => $documentPath[0]
            ]));
            exit;
        }

        $_array = array();

        array_shift($documentName);
        array_shift($documentUrl);
        array_shift($documentPath);

        $_array["Name"] = trim(implode(":", $documentName));
        $_array["Url"] = trim(implode(":", $documentUrl));
        $_array["Path"] = trim(implode(":", $documentPath));

        $parsed["Documents"][] = $_array;
    }

    echo crisp\core\PluginAPI::response(crisp\core\Bitmask::REQUEST_SUCCESS, var_export($parsed, true));
    exit;
}