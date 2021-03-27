<?php

if (!is_numeric($this->Query)) {
    if (!crisp\api\Phoenix::serviceExistsBySlugPG($this->Query)) {
        echo \crisp\core\PluginAPI::response(\crisp\core\Bitmask::INVALID_SERVICE, $this->Query, []);
        return;
    }
    $this->Query = crisp\api\Phoenix::getServiceBySlugPG($this->Query)["id"];
    $SkeletonData = \crisp\api\Phoenix::generateApiFiles($this->Query);
    echo \crisp\core\PluginAPI::response(\crisp\core\Bitmask::REQUEST_SUCCESS, $this->Query, \crisp\api\Phoenix::generateApiFiles($this->Query, "3"));
    exit;
}

if (!crisp\api\Phoenix::serviceExistsPG($this->Query)) {
    echo \crisp\core\PluginAPI::response(\crisp\core\Bitmask::INVALID_SERVICE, $this->Query, []);
    return;
}



echo \crisp\core\PluginAPI::response(\crisp\core\Bitmask::REQUEST_SUCCESS, $this->Query, \crisp\api\Phoenix::generateApiFiles($this->Query, "3"));