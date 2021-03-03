<?php

/*
 * Copyright 2021 Pixelcat Productions <support@pixelcatproductions.net>
 * @author 2021 Justin René Back <jback@pixelcatproductions.net>
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
 * New Permission System
 * 
 * DO NOT AUTOLOAD YET, NOT FULLY TESTED
 */
class Bitmask {

    use \crisp\core\Hook;

    /**
     * Maybe we should use categories for permissions?
     */
    public const PURGE_CACHE = 0x00000001;
    public const MERGE_SERVICE = 0x00000002;
    public const DELETE_SERVICE = 0x00000004;
    public const MOVE_POINTS = 0x00000008;
    public const DELETE_POINTS = 0x00000010;
    public const MERGE_POINTS = 0x00000020;
    public const DELETE_DOCUMENT = 0x00000040;
    public const CRAWL_DOCUMENT = 0x00000080;
    public const PROMOTE_TO_BOT = 0x00000100;
    public const PROMOTE_TO_CURATOR = 0x00000200;
    public const PROMOTE_TO_ADMIN = 0x00000400;
    public const RESET_PASSWORD = 0x00000800;
    public const CHANGE_PASSWORD = 0x00001000;
    public const SUPERUSER = 0x00002000;
    public const MERGE_CASE = 0x00004000;
    public const CHANGE_KV = 0x00008000;
    public const INSTALL_PLUGINS = 0x00010000;
    public const UNINSTALL_PLUGINS = 0x00020000;
    public const REFRESH_PLUGINS_KV = 0x00040000;
    public const REFRESH_PLUGINS_TRANSLATIONS = 0x00080000;
    public const UPDATE_PLUGINS = 0x00100000;
    public const PURGE_PLUGINS = 0x00200000;

    public static function hasBitmask(int $BitwisePermissions, int $PermissionFlag = 0x00000000) {
        if (!is_numeric($BitwisePermissions)) {
            throw new \TypeError("Parameter BitwisePermissions is not a hexadecimal or number.");
        }
        if (!is_numeric($PermissionFlag)) {
            throw new \TypeError("Parameter PermissionFlag is not a hexadecimal or number.");
        }

        if ($BitwisePermissions === 0x00000000) {
            return true;
        }
        return ($BitwisePermissions & $PermissionFlag ? true : false);
    }

}
