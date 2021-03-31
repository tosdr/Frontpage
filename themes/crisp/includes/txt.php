<?php

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

    $parsed = array();

    $countDocuments = substr_count($txtFile, "Document-Name:");
    for ($i = 0; $i < $countDocuments; $i++) {



        $firstDocumentLine = getLineWithString($txtFileExploded, "Document-Name:");

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

        $parsed[] = $_array;
    }

    echo crisp\core\PluginAPI::response(crisp\core\Bitmask::REQUEST_SUCCESS, var_export($parsed, true));
    exit;
}