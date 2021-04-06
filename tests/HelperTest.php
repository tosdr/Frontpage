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

use PHPUnit\Framework\TestCase;

final class HelperTest extends TestCase {

    public function testFilterAlphaNum(): void {
        $this->assertEquals(
                'test-filter',
                \crisp\api\Helper::filterAlphaNum('Test Filter')
        );
        $this->assertEquals(
                'test_filter',
                \crisp\api\Helper::filterAlphaNum('test_filter')
        );
        $this->assertEquals(
                'test-filter',
                \crisp\api\Helper::filterAlphaNum('test-filter')
        );
    }

    public function testStartsWith(): void {
        $this->assertEquals(
                true,
                \crisp\api\Helper::startsWith('Test Filter', 'Test')
        );
        $this->assertEquals(
                false,
                \crisp\api\Helper::startsWith('Test Filter', 'Filter')
        );
    }

    public function testEndsWith(): void {
        $this->assertEquals(
                true,
                \crisp\api\Helper::endsWith('Test Filter', 'Filter')
        );
        $this->assertEquals(
                false,
                \crisp\api\Helper::endsWith('Test Filter', 'Test')
        );
    }

    public function testTemplateExists(): void {
        $this->assertEquals(
                true,
                \crisp\api\Helper::templateExists('crisp', 'base.twig')
        );
        $this->assertEquals(
                false,
                \crisp\api\Helper::templateExists('crisp', 'errors/_notfound.twig')
        );
    }

    public function testTruncateText(): void {
        $this->assertEquals(
                "cr...",
                \crisp\api\Helper::truncateText('crisp', 2)
        );
        $this->assertEquals(
                "cr",
                \crisp\api\Helper::truncateText('crisp', 2, false)
        );
    }

    public function testIsSerialized(): void {
        $this->assertEquals(
                false,
                \crisp\api\Helper::isSerialized('unserialized')
        );
        $this->assertEquals(
                true,
                \crisp\api\Helper::isSerialized(serialize('serialize me'))
        );
        $this->assertEquals(
                true,
                \crisp\api\Helper::isSerialized(serialize(array()))
        );
    }

    public function testIsMobile(): void {
        $this->assertEquals(
                false,
                \crisp\api\Helper::isMobile('unserialized')
        );
        $this->assertEquals(
                true,
                \crisp\api\Helper::isMobile('Mozilla/5.0 (Linux; Android 8.0.0; SM-G960F Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.84 Mobile Safari/537.36')
        );
        $this->assertEquals(
                true,
                \crisp\api\Helper::isMobile('Mozilla/5.0 (iPhone; CPU iPhone OS 12_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/12.0 Mobile/15E148 Safari/604.1')
        );
        $this->assertEquals(
                true,
                \crisp\api\Helper::isMobile('Mozilla/5.0 (Linux; Android 7.0; Pixel C Build/NRD90M; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/52.0.2743.98 Safari/537.36')
        );
        $this->assertEquals(
                true,
                \crisp\api\Helper::isMobile('Mozilla/5.0 (Windows Phone 10.0; Android 6.0.1; Microsoft; RM-1152) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/52.0.2743.116 Mobile Safari/537.36 Edge/15.15254')
        );
        $this->assertEquals(
                false,
                \crisp\api\Helper::isMobile('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/42.0.2311.135 Safari/537.36 Edge/12.246')
        );
        $this->assertEquals(
                false,
                \crisp\api\Helper::isMobile('Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_2) AppleWebKit/601.3.9 (KHTML, like Gecko) Version/9.0.2 Safari/601.3.9')
        );
        $this->assertEquals(
                false,
                \crisp\api\Helper::isMobile('Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:15.0) Gecko/20100101 Firefox/15.0.1')
        );
    }

    public function testIsValidPluginName(): void {
        $this->assertEquals(
                true,
                \crisp\api\Helper::isValidPluginName('plugin')
        );
        $this->assertEquals(
                ["STRING_CONTAINS_NON_ALPHA_NUM", "STRING_CONTAINS_SPACES", "STRING_CONTAINS_UPPERCASE"],
                \crisp\api\Helper::isValidPluginName('match ALL three?')
        );
        $this->assertEquals(
                ["STRING_CONTAINS_NON_ALPHA_NUM", "STRING_CONTAINS_SPACES"],
                \crisp\api\Helper::isValidPluginName('match two')
        );
        $this->assertEquals(
                ["STRING_CONTAINS_NON_ALPHA_NUM"],
                \crisp\api\Helper::isValidPluginName('matchalpha?')
        );
        $this->assertEquals(
                ["STRING_CONTAINS_UPPERCASE"],
                \crisp\api\Helper::isValidPluginName('UPPERCASE')
        );
    }

}
