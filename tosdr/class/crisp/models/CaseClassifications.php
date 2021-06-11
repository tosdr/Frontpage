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

namespace crisp\models;

use crisp\types\Enum;

if (!defined('CRISP_COMPONENT')) {
    echo 'Cannot access this component directly!';
    exit;
}

final class CaseClassifications extends Enum
{

    public const blocker = 0x1;
    public const bad = 0x2;
    public const neutral = 0x4;
    public const good = 0x8;
    public const unknown = 0x10;
    public const default = 0x10;

}
