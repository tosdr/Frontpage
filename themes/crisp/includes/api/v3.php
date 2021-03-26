<?php

if ($this->Query == "all") {
    $Services = \crisp\api\Phoenix::getServicesPG();
    $Response = array(
        "version" => time(),
    );
    foreach ($Services as $Index => $Service) {

        $Service["urls"] = explode(",", $Service["url"]);
        $Service["nice_service"] = \crisp\api\Helper::filterAlphaNum($Service["name"]);
        $Service["has_image"] = (file_exists(__DIR__ . "/../" . \crisp\api\Config::get("theme_dir") . "/" . \crisp\api\Config::get("theme") . "/img/logo/" . $Service["nice_service"] . ".svg") ? true : file_exists(__DIR__ . "/../" . \crisp\api\Config::get("theme_dir") . "/" . \crisp\api\Config::get("theme") . "/img/logo/" . $Service["nice_service"] . ".png") );
        $Service["logo"] = crisp\core\Themes::includeResource("img/logo/" . \crisp\api\Helper::filterAlphaNum($Service["name"]) . ".png");

        $Services[$Index] = $Service;
    }

    $Response["services"] = $Services;

    echo \crisp\core\PluginAPI::response(false, "All services below", $Response);

    return;
}

if (!is_numeric($this->Query)) {
    if (!crisp\api\Phoenix::serviceExistsBySlugPG($this->Query)) {
        echo \crisp\core\PluginAPI::response(\crisp\core\Bitmask::INVALID_SERVICE + crisp\core\Bitmask::VERSION_DEPRECATED + crisp\core\Bitmask::INTERFACE_DEPRECATED, $this->Query, []);
        return;
    }
    $this->Query = crisp\api\Phoenix::getServiceBySlugPG($this->Query)["id"];
    $SkeletonData = \crisp\api\Phoenix::generateApiFiles($this->Query);
    echo \crisp\core\PluginAPI::response(\crisp\core\Bitmask::REQUEST_SUCCESS + crisp\core\Bitmask::VERSION_DEPRECATED + crisp\core\Bitmask::INTERFACE_DEPRECATED, $this->Query, \crisp\api\Phoenix::generateApiFiles($this->Query, "3"));
    exit;
}

if (!crisp\api\Phoenix::serviceExistsPG($this->Query)) {
    echo \crisp\core\PluginAPI::response(\crisp\core\Bitmask::INVALID_SERVICE + crisp\core\Bitmask::VERSION_DEPRECATED + crisp\core\Bitmask::INTERFACE_DEPRECATED, $this->Query, []);
    return;
}


echo \crisp\core\PluginAPI::response(\crisp\core\Bitmask::REQUEST_SUCCESS + crisp\core\Bitmask::VERSION_DEPRECATED + crisp\core\Bitmask::INTERFACE_DEPRECATED, $this->Query, \crisp\api\Phoenix::generateApiFiles($this->Query, "3"));
