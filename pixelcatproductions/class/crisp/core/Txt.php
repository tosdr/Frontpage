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

namespace crisp\core;

class Txt {

    public const ALLOWED_KEYS = ["#", "Domains", "Document-Name", "Url", "Path", "ID"];

    public static function parse($Document, $url = false) {
        $parsed = array();
        $failed = array();
        $DocumentExploded = explode("\n", $Document);



        foreach ($DocumentExploded as $key => $line) {

            if (!empty($line)) {
                if (!in_array(explode(":", $line)[0], self::ALLOWED_KEYS)) {

                    if (!\crisp\api\Helper::startsWith($line, "#")) {

                        throw new \crisp\exceptions\BitmaskException(\crisp\api\Translation::fetch("views.txt.errors.invalid_key", 1, [
                                    "{{ line }}" => $key + 1,
                                    "{{ got }}" => (explode(":", $line)[0] ?? explode(" ", $line)[0] ?? "??")
                                ]), Bitmask::QUERY_FAILED);
                    }
                }
                $DocumentExploded[$key] = preg_replace("/\#.+/", "", $line);
            }

            foreach (self::ALLOWED_KEYS as $allowedKey) {

                if (strpos($line, "$allowedKey:") !== false) {
                    continue 2;
                }
            }

            unset($DocumentExploded[$key]);
        }

        // Duplicate keys check

        $_doctmp = implode("\n", $DocumentExploded);
        foreach (self::ALLOWED_KEYS as $allowedKey) {
            if (substr_count($_doctmp, "$allowedKey:") === 1) {
                continue;
            } else {
                if (substr_count($_doctmp, "$allowedKey:") > 1) {
                    if ($allowedKey === "Document-Name" || $allowedKey === "Url" || $allowedKey === "Path") {
                        continue;
                    }
                    $failed["duplicate_keys"][] = $allowedKey;
                }
            }
        }



        /* Domains */

        if (!is_numeric(array_search("Domains", $failed["duplicate_keys"]))) {

            $domainLine = getLineWithString($DocumentExploded, "Domains:");

            if ($domainLine === -1) {
                $failed["missing_domain_list"] = true;
            } else {


                $dmarray = array();

                $explodedDomains = explode(":", $DocumentExploded[$domainLine]);

                array_shift($explodedDomains);

                $domains = explode(",", implode(":", $explodedDomains));

                $domainRegex = '/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z0-9][a-z0-9-]{0,61}[a-z0-9]$/';
                foreach ($domains as $domain) {

                    if (empty(trim($domain))) {
                        $failed["invalid_domain_list"][] = trim($domain);
                    } else if (preg_match($domainRegex, trim($domain))) {
                        $dmarray[] = trim($domain);
                        continue;
                    }

                    $failed["invalid_domain_list"][] = trim($domain);
                    //throw new \crisp\exceptions\BitmaskException(\crisp\api\Translation::fetch("views.txt.errors.invalid_domain_list", 1, [
                    //            "{{ domain }}" => trim($domain),
                    //        ]), Bitmask::QUERY_FAILED);
                }

                $parsed["Domains"] = $dmarray;

                unset($DocumentExploded[$domainLine]);
            }
        }

        /* End Domains */


        /* ID */

        if (!is_numeric(array_search("Domains", $failed["duplicate_keys"])) && !is_numeric(array_search("ID", $failed["duplicate_keys"]))) {

            $idLine = getLineWithString($DocumentExploded, "ID:");


            if ($idLine !== -1 && $url !== false) {
                $explodedID = explode(":", $DocumentExploded[$idLine]);
                array_shift($explodedID);
                $ID = trim(implode(":", $explodedID));

                if (!is_numeric($ID)) {
                    $failed["id_not_numeric"][] = trim($ID);
                }
                if ($ID < 1) {
                    $failed["id_invalid"][] = trim($ID);
                }


                if (count($failed["id_not_numeric"]) === 0) {
                    $Service = \crisp\api\Phoenix::getServicePG($ID)["_source"];
                    if ($Service && in_array($url, explode(",", $Service["url"]))) {
                        $parsed["ID"] = $Service;
                    } else {
                        $failed["id_no_match_domains"][] = trim($url);
                    }
                }

                unset($DocumentExploded[$idLine]);
            }
        }



        /* End ID */


        /* Documents */

        $countDocuments = substr_count($Document, "Document-Name:");

        if ($countDocuments === 0) {
            $failed["missing_documents"] = -1;
        } else {

            for ($i = 0; $i < $countDocuments; $i++) {

                $firstDocumentLine = getLineWithString($DocumentExploded, "Document-Name:");

                if ($firstDocumentLine === -1) {
                    $failed["missing_document_name"][] = -1;
                } else {


                    $documentName = explode(":", $DocumentExploded[$firstDocumentLine]);
                    $documentUrl = explode(":", $DocumentExploded[$firstDocumentLine + 1]);
                    $documentPath = explode(":", $DocumentExploded[$firstDocumentLine + 2]);

                    #array_shift($DocumentExploded);
                    #array_shift($DocumentExploded);
                    #array_shift($DocumentExploded);


                    unset($DocumentExploded[$firstDocumentLine]);
                    unset($DocumentExploded[$firstDocumentLine + 1]);
                    unset($DocumentExploded[$firstDocumentLine + 2]);

                    if ($documentName[0] !== "Document-Name") {
                        $failed["missing_document_name"][] = array(
                            "line" => $firstDocumentLine + 1,
                            "expected" => "Document-Name",
                            "got" => $documentName[0]
                        );
                    } else {
                        array_shift($documentName);
                    }
                    if ($documentUrl[0] !== "Url") {
                        $failed["missing_document_url"][] = array(
                            "line" => $firstDocumentLine + 2,
                            "expected" => "Url",
                            "got" => $documentUrl[0]
                        );
                    } else {
                        array_shift($documentUrl);
                    }

                    if ($documentPath[0] !== "Path") {
                        $failed["missing_document_path"][] = array(
                            "line" => $firstDocumentLine + 3,
                            "expected" => "Path",
                            "got" => $documentUrl[0]
                        );
                    } else {
                        array_shift($documentPath);
                    }

                    $_array = array();


                    $_array["Name"] = trim(implode(":", $documentName));
                    $_array["Url"] = trim(implode(":", $documentUrl));
                    $_array["Path"] = trim(implode(":", $documentPath));

                    $parsed["Documents"][] = $_array;
                }
            }
        }
        /* End Documents */

        return array("results" => $parsed, "failed_validations" => $failed);
    }

}
