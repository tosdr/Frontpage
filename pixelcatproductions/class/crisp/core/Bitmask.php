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
 * API Error Codes
 */
class Bitmask {

    use \crisp\core\Hook;

    /**
     * Maybe we should use categories for permissions?
     */
    public const NONE = 0x0;
    public const INVALID_SERVICE = 0x1;
    public const INTERFACE_NOT_FOUND = 0x2;
    public const GENERATE_FAILED = 0x4;
    public const INVALID_PLUGIN_NAME = 0x8;
    public const QUERY_FAILED = 0x10;
    public const METHOD_DEPRECATED = 0x20;
    public const INTERFACE_DEPRECATED = 0x40;
    public const VERSION_DEPRECATED = 0x80;
    public const REQUEST_SUCCESS = 0x100; // Request went through just fine. Used in new versions
    public const VERSION_NOT_FOUND = 0x200;
    public const INVALID_CASE = 0x400;
    public const INVALID_TOPIC = 0x800;
    public const INVALID_POINT = 0x1000;
    public const METHOD_NOT_ALLOWED = 0x2000; // Send this along with a 405
    public const NOT_IMPLEMENTED = 0x4000; // Send this along with a 501
    public const MISSING_PARAMETER = 0x8000;
    public const INVALID_PARAMETER = 0x10000;
    public const GENERIC_ERROR = 0x20000;
    public const SERVICE_DUPLICATE = 0x40000;
    public const INVALID_SUBNET = 0x80000;

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

    public static function getConstants() {
        $oClass = new \ReflectionClass(__CLASS__);
        return $oClass->getConstants();
    }

    public static function getBitmask(int $BitwisePermissions) {
        if (!is_numeric($BitwisePermissions)) {
            throw new \TypeError("Parameter BitwisePermissions is not a hexadecimal or number.");
        }
        if ($BitwisePermissions === 0x00000000) {
            throw new \TypeError("Parameter BitwisePermissions is zero.");
        }

        $MatchedBits = [];

        foreach (self::getConstants() as $Permission) {
            if (self::hasBitmask($BitwisePermissions, $Permission)) {
                $MatchedBits[array_search($Permission, self::getConstants())] = $Permission;
            }
        }
        return $MatchedBits;
    }

}
