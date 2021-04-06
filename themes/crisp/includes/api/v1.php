<?php

header("Content-Type: application/json");

if ($this->Query == "all") {
    $Services = \crisp\api\Phoenix::getServicesPG();
    $Response = array(
        "tosdr/api/version" => 1,
        "tosdr/data/version" => time(),
    );
    foreach ($Services as $Service) {
        $URLS = explode(",", $Service["url"]);
        foreach ($URLS as $URL) {
            $URL = trim($URL);
            $Response["tosdr/review/$URL"] = array(
                "id" => (int) $Service["id"],
                "documents" => [],
                "logo" => \crisp\api\Config::get("s3_logos") . "/" . $Service["id"] . ".png",
                "name" => $Service["name"],
                "slug" => $Service["slug"],
                "rated" => ($Service["rating"] == "N/A" ? false : ($Service["is_comprehensively_reviewed"] ? $Service["rating"] : false)),
                "points" => []
            );
        }
    }
    echo json_encode($Response);
    return;
}

if (!is_numeric($this->Query)) {
    if (!crisp\api\Phoenix::serviceExistsBySlugPG($this->Query)) {
        echo \crisp\core\PluginAPI::response(\crisp\core\Bitmask::INVALID_SERVICE, $this->Query, [], null, 404);
        return;
    }
    $this->Query = crisp\api\Phoenix::getServiceBySlugPG($this->Query)["id"];
    
    $SkeletonData = \crisp\api\Phoenix::generateApiFiles($this->Query);
    echo json_encode($SkeletonData);


    exit;
}



if (!crisp\api\Phoenix::serviceExistsPG($this->Query)) {
    echo \crisp\core\PluginAPI::response(\crisp\core\Bitmask::INVALID_SERVICE, $this->Query, [], null, 404);
    return;
}

$SkeletonData = \crisp\api\Phoenix::generateApiFiles($this->Query);

echo json_encode($SkeletonData);
