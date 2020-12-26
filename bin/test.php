<?php

define('CRISP_CLI', true);
define('CRISP_API', true);
define('NO_KMS', true);

if (php_sapi_name() !== 'cli') {
    echo "Not from CLI";
    exit;
}


error_reporting(error_reporting() & ~E_NOTICE);
require_once __DIR__ . "/../pixelcatproductions/crisp.php";


crisp\core\Themes::installTranslations("test", crisp\core\Themes::getThemeMetadata("test"));