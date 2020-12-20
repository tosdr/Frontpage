<?php

/*
 * Copyright 2020 Pixelcat Productions <support@pixelcatproductions.net>
 * @author 2020 Justin René Back <jback@pixelcatproductions.net>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace crisp\core;

/**
 * Used internally, plugin loader
 *
 */
class Templates {

    use \crisp\core\Hook;

    public static function clearCache() {
        $it = new \RecursiveDirectoryIterator(realpath(__DIR__ . "/../../../cache/"), \RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new \RecursiveIteratorIterator($it,
                \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }
        return true;
    }

    public static function load($TwigTheme, $CurrentFile, $CurrentPage) {
        if (\crisp\api\Helper::templateExists(\crisp\api\Config::get("theme"), "/views/$CurrentPage.twig")) {
            new \crisp\core\Template($TwigTheme, $CurrentFile, $CurrentPage);
        } else {
            echo $TwigTheme->render("errors/404.twig");
        }
    }

}
