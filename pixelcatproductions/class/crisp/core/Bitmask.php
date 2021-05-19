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
 * API Error Codes
 */
class Bitmask extends \crisp\types\Bitmask {

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
    public const POSTGRES_CONN_ERROR = 0x100000;
    public const POSTGRES_QUERY_MALFORMED = 0x200000;
    public const ELASTIC_CONN_ERROR = 0x400000;
    public const ELASTIC_QUERY_MALFORMED = 0x800000;
    public const REDIS_CONN_ERROR = 0x1000000;
    public const REDIS_QUERY_MALFORMED = 0x2000000;
    public const TWIG_ERROR = 0x4000000;
    public const PLUGIN_MISSING_INCLUDES = 0x8000000;
    public const THEME_MISSING_INCLUDES = 0x10000000;
    public const MISSING_PERMISSIONS = 0x20000000;

}
