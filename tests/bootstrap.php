<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of bootstrap
 *
 * @author Justin Back
 */
class bootstrap extends PHPUnit_Framework_TestCase {

    public function testInit() {
        $this->assertEquals(true, \crisp\api\Config::create("plugin_heroku_database", "postgres://tosdr_dev:tosdr_dev@postgres:5432/tosdr_dev"));
    }

}
