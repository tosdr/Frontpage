<?php

/*
 * Copyright (C) 2020 Justin Back <jback@pixelcatproductions.net>
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

namespace crisp;

/**
 * Crisp Universe Handling
 */
class Universe {

    const UNIVERSE_PUBLIC = 1;
    const UNIVERSE_BETA = 2;
    const UNIVERSE_DEV = 3;

    public static function changeUniverse($Universe) {
        return $_SESSION[core\Config::$Cookie_Prefix . "universe"] = self::getUniverse($Universe);
    }

    public static function getUniverse($Universe) {
        switch ($Universe) {
            case self::UNIVERSE_PUBLIC:
                return self::UNIVERSE_PUBLIC;
            case self::UNIVERSE_BETA:
                return self::UNIVERSE_BETA;
            case self::UNIVERSE_DEV:
                return self::UNIVERSE_DEV;
            default:
                return self::UNIVERSE_PUBLIC;
        }
    }

    public static function getUniverseName($value) {
        $class = new \ReflectionClass(__CLASS__);
        $constants = array_flip($class->getConstants());

        return $constants[$value];
    }

}
