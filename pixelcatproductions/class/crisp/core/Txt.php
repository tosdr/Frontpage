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



        /* Domains */

        $domainLine = getLineWithString($DocumentExploded, "Domains:");

        if ($domainLine === -1) {
            throw new \crisp\exceptions\BitmaskException(\crisp\api\Translation::fetch("views.txt.errors.invalid_line", 1, [
                        "{{ line }}" => -1,
                        "{{ expected }}" => "Domains",
                        "{{ got }}" => "Nothing"
                    ]), Bitmask::QUERY_FAILED);
        }


        $dmarray = array();

        $domains = explode(",", explode(":", $DocumentExploded[$domainLine])[1]);

        $domainRegex = '/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z0-9][a-z0-9-]{0,61}[a-z0-9]$/';
        foreach ($domains as $domain) {

            if (empty(trim($domain))) {
                throw new \crisp\exceptions\BitmaskException(\crisp\api\Translation::fetch("views.txt.errors.invalid_domain_list", 1, [
                            "{{ domain }}" => trim($domain),
                        ]), Bitmask::QUERY_FAILED);
            }

            if (preg_match($domainRegex, trim($domain))) {
                $dmarray[] = trim($domain);
                continue;
            }
            throw new \crisp\exceptions\BitmaskException(\crisp\api\Translation::fetch("views.txt.errors.invalid_domain_list", 1, [
                        "{{ domain }}" => trim($domain),
                    ]), Bitmask::QUERY_FAILED);
        }

        $parsed["Domains"] = $dmarray;

        unset($DocumentExploded[$domainLine]);

        /* End Domains */


        /* ID */

        $idLine = getLineWithString($DocumentExploded, "ID:");


        if ($idLine !== -1 && $url !== false) {
            $ID = explode(":", $DocumentExploded[$idLine])[1];
            $Service = \crisp\api\Phoenix::getServicePG($ID)["_source"];
            if ($Service && in_array($url, explode(",", $Service["url"]))) {
                $parsed["ID"] = $Service;
            }

            unset($DocumentExploded[$idLine]);
        }




        /* End URL */


        /* Documents */

        $countDocuments = substr_count($Document, "Document-Name:");
        for ($i = 0; $i < $countDocuments; $i++) {

            $firstDocumentLine = getLineWithString($DocumentExploded, "Document-Name:");

            if ($firstDocumentLine === -1) {
                throw new \crisp\exceptions\BitmaskException(\crisp\api\Translation::fetch("views.txt.errors.invalid_line", 1, [
                            "{{ line }}" => -1,
                            "{{ expected }}" => "Document-Name",
                            "{{ got }}" => "Nothing"
                        ]), crisp\core\Bitmask::QUERY_FAILED);
            }


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
                throw new \crisp\exceptions\BitmaskException(\crisp\api\Translation::fetch("views.txt.errors.invalid_line", 1, [
                            "{{ line }}" => $firstDocumentLine + 1,
                            "{{ expected }}" => "Document-Name",
                            "{{ got }}" => $documentName[0]
                        ]), Bitmask::QUERY_FAILED);
            }

            if ($documentUrl[0] !== "Url") {
                throw new \crisp\exceptions\BitmaskException(\crisp\api\Translation::fetch("views.txt.errors.invalid_line", 1, [
                            "{{ line }}" => $firstDocumentLine + 2,
                            "{{ expected }}" => "Url",
                            "{{ got }}" => $documentUrl[0]
                        ]), Bitmask::QUERY_FAILED);
            }

            if ($documentPath[0] !== "Path") {
                throw new \crisp\exceptions\BitmaskException(\crisp\api\Translation::fetch("views.txt.errors.invalid_line", 1, [
                            "{{ line }}" => $firstDocumentLine + 3,
                            "{{ expected }}" => "Path",
                            "{{ got }}" => $documentPath[0]
                        ]), Bitmask::QUERY_FAILED);
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
        /* End Documents */

        return $parsed;
    }

}
