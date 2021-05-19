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

use crisp\core\Config;
use Exception;
use ReflectionClass;

/**
 * Crisp Universe Handling
 */
class Universe {

  const UNIVERSE_PUBLIC = 1;
  const UNIVERSE_BETA = 2;
  const UNIVERSE_DEV = 3;
  const UNIVERSE_TOSDR = 99;

  public static function changeUniverse($Universe, $Authorize = false): bool
  {
    if (!$Authorize && $Universe == self::UNIVERSE_TOSDR) {
      return false;
    }
    return setcookie(Config::$Cookie_Prefix . "universe", self::getUniverse($Universe), time() + (86400 * 30), "/");
  }

    /**
     * @param int $Universe
     * @return int
     * @throws Exception
     */
    public static function getUniverse(int $Universe): int
  {
      return match ($Universe) {
          self::UNIVERSE_PUBLIC => self::UNIVERSE_PUBLIC,
          self::UNIVERSE_BETA => self::UNIVERSE_BETA,
          self::UNIVERSE_DEV => self::UNIVERSE_DEV,
          self::UNIVERSE_TOSDR => self::UNIVERSE_TOSDR,
          default => throw new Exception("Unknown universe"),
      };
  }

  public static function getUniverseName($value): string
  {
    $class = new ReflectionClass(__CLASS__);
    $constants = array_flip($class->getConstants());

    return $constants[$value];
  }

}
