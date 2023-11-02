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

namespace tosdr\PageControllers;

use crisp\api\Config;
use crisp\api\Helper;
use crisp\core\Themes;
use crisp\core\ThemeVariables;

class FrontpagePageController
{

    public function preRender(): void
    {

        $Services = [];


        if (!isset($_GET['search'])) {
            foreach (Config::get('frontpage_services') as $ID) {
                $Service = \tosdr\Phoenix::getService($ID);
                $Services[] = $Service;
            }
        } else {
            $Services = \tosdr\Phoenix::searchServiceByName(strtolower($_GET['search']));
        }
        
        
        $OneToFour = array_slice($Services, 0, 4);
        $FiveToSeven = array_slice(array_slice($Services, 0, 8), 4);
        $EightToEleven = array_slice(array_slice($Services, 0, 12), 8);
        $ElevenToFourteen = array_slice(array_slice($Services, 0, 16), 12);
        $Rest = array_slice($Services, 16);
        
        ThemeVariables::setMultiple([
            'AllServices' => $Services,
            'RestServices' => $Rest,
            'OneToFour' => $OneToFour,
            'FiveToSeven' => $FiveToSeven,
            'EightToEleven' => $EightToEleven,
            'ElevenToFourteen' => $ElevenToFourteen
        ]);

        echo Themes::render("views/frontpage.twig");
    }
}
