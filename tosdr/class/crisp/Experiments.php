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


namespace crisp;

use crisp\api\Helper;
use crisp\core\Config;
use crisp\types\Bitmask;
use Exception;
use ReflectionClass;
use TypeError;

if (!defined('CRISP_COMPONENT')) {
    echo 'Cannot access this component directly!';
    exit;
}

/**
 * Crisp Universe Handling
 */
class Experiments extends Bitmask
{
    public const NONE = 0x0;
    public const FRONTPAGE_REDESIGN_2021_07 = 0x1;

    public static function optIn(int $experiment): bool
    {
        if (!isset($_COOKIE[Config::$Cookie_Prefix . 'experiments'])) {
            return setcookie(Config::$Cookie_Prefix . 'experiments', $experiment, time() + (86400 * 7), '/');
        }


        if (self::validValue($experiment) && !self::hasBitmask($_COOKIE[Config::$Cookie_Prefix . 'experiments'], $experiment)) {
            return setcookie(Config::$Cookie_Prefix . 'experiments', $_COOKIE[Config::$Cookie_Prefix . 'experiments'] + $experiment, time() + (86400 * 7), '/');
        }

        return false;
    }


    public static function optOut(int $experiment): bool
    {
        if (!isset($_COOKIE[Config::$Cookie_Prefix . 'experiments'])) {
            return setcookie(Config::$Cookie_Prefix . 'experiments', self::NONE, time() + (86400 * 7), '/');
        }


        if (self::validValue($experiment) && self::hasBitmask($_COOKIE[Config::$Cookie_Prefix . 'experiments'], $experiment)) {
            return setcookie(Config::$Cookie_Prefix . 'experiments', $_COOKIE[Config::$Cookie_Prefix . 'experiments'] - $experiment, time() + (86400 * 7), '/');
        }

        return false;
    }


    public static function isActive(int $experiment): bool
    {
        if (!isset($_COOKIE[Config::$Cookie_Prefix . 'experiments'])) {
            return false;
        }


        if (self::validValue($experiment) && self::hasBitmask($_COOKIE[Config::$Cookie_Prefix . 'experiments'], $experiment)) {
            return true;
        }

        return false;
    }

    public static function getExperiments(): array
    {
        if (!isset($_COOKIE[Config::$Cookie_Prefix . 'experiments'])) {
            return [];
        }


        try {
            return self::getBitmask($_COOKIE[Config::$Cookie_Prefix . 'experiments']);
        } catch (TypeError) {
            return [];
        }
    }

    public static function hasAnyExperiment(): bool
    {
        if (!isset($_COOKIE[Config::$Cookie_Prefix . 'experiments'])) {
            return false;
        }

        return !($_COOKIE[Config::$Cookie_Prefix . 'experiments'] === '0');
    }


    public static function assignAB(): bool
    {

        $Disallowed = [];


        if (isset($_COOKIE[Config::$Cookie_Prefix . 'experiments'])) {
            return false;
        }

        if (random_int(0, 99) < 75) {
            return self::optIn(self::NONE);
        }

        $Experiment = self::get(array_rand(self::getConstants()));


        if (in_array($Experiment, $Disallowed, true)) {
            return false;
        }

        return self::optIn($Experiment);
    }

}
