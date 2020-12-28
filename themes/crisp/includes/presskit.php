<?php

if (isset($_GET["download"])) {
    header("Content-type: application/zip");
    header("Content-Disposition: attachment; filename=Presskit.zip");
    header("Content-length: " . filesize(__DIR__."/../presskit/ToSDR.zip"));
    header("Pragma: no-cache");
    header("Expires: 0");
    readfile(__DIR__."/../presskit/ToSDR.zip");
    exit;
}