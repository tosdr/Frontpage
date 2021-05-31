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
 * API Permission Bitmask
 */
class APIPermissions extends \crisp\types\Bitmask {

    public const NONE = 0x1;
    public const POST_SERVICE_REQUEST = 0x2;
    public const GET_API_KEY_DETAILS = 0x4;
    public const CAN_USE_CRAWLER = 0x8;
    public const CAN_USE_DOCBOT = 0x10;
    public const CAN_USE_OAUTH = 0x20;
    public const OAUTH_CAN_SEE_EMAIL = 0x40;
    public const OAUTH_CAN_SEE_USERNAME = 0x80;
    public const OAUTH_CAN_POST_SERVICE_COMMENT = 0x100;
    public const OAUTH_CAN_POST_POINT_COMMENT = 0x200;
    public const OAUTH_CAN_POST_TOPIC_COMMENT = 0x400;
    public const OAUTH_CAN_POST_CASE_COMMENT = 0x800;
    public const OAUTH_CAN_CREATE_POINT = 0x1000;
    public const OAUTH_CAN_CREATE_CASE = 0x2000;
    public const OAUTH_CAN_CREATE_TOPIC = 0x4000;
    public const OAUTH_CAN_CREATE_SERVICE = 0x8000;
    public const OAUTH_CAN_EDIT_SERVICE = 0x10000;
    public const OAUTH_CAN_EDIT_TOPIC = 0x20000;
    public const OAUTH_CAN_EDIT_CASE = 0x40000;
    public const OAUTH_CAN_EDIT_POINT = 0x80000;
    public const OAUTH_READ_USER = 0x100000;
}
