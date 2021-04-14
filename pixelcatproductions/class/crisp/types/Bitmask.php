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

namespace crisp\types;

abstract class Bitmask extends Enum {

    public static function hasBitmask(int $BitwisePermissions, int $PermissionFlag = 0x00000000) {
        if (!is_numeric($BitwisePermissions)) {
            throw new \TypeError("Parameter BitwisePermissions is not a hexadecimal or number.");
        }
        if (!is_numeric($PermissionFlag)) {
            throw new \TypeError("Parameter PermissionFlag is not a hexadecimal or number.");
        }

        if ($PermissionFlag === 0x00000000) {
            return true;
        }
        return ($BitwisePermissions & $PermissionFlag ? true : false);
    }

    public static function getConstants() {
        $oClass = new \ReflectionClass(static::class);
        return $oClass->getConstants();
    }

    public static function getBitmask(int $BitwisePermissions, bool $IndexArray = false) {
        if (!is_numeric($BitwisePermissions)) {
            throw new \TypeError("Parameter BitwisePermissions is not a hexadecimal or number.");
        }
        if ($BitwisePermissions === 0x00000000) {
            throw new \TypeError("Parameter BitwisePermissions is zero.");
        }

        $MatchedBits = [];

        foreach (self::getConstants() as $Permission) {

            if (self::hasBitmask($BitwisePermissions, $Permission)) {
                if ($IndexArray) {
                    $MatchedBits[] = array_search($Permission, self::getConstants());
                } else {
                    $MatchedBits[array_search($Permission, self::getConstants())] = $Permission;
                }
            }
        }
        return $MatchedBits;
    }

}
