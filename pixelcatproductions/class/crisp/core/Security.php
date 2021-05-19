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

namespace crisp\core;

/**
 * Totally unfinished
 */
class Security {

    /**
     * @return string
     */
    public static function getCSRF(): string
    {
        if (!isset($_SESSION["csrf"])) {
            $_SESSION["csrf"] = bin2hex(openssl_random_pseudo_bytes(16));
        }
        return $_SESSION["csrf"];
    }

    /**
     * @param string $Token
     * @return bool
     */
    public static function matchCSRF(string $Token): bool
    {
        return ($_SESSION["csrf"] && $Token);
    }

    public static function regenCSRF() {
        $_SESSION["csrf"] = bin2hex(openssl_random_pseudo_bytes(16));
        return $_SESSION["csrf"];
    }

}
