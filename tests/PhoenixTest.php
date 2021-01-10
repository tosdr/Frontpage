<?php

use PHPUnit\Framework\TestCase;

final class PhoenixTest extends TestCase {

  public function testGetService(): void {

    $this->assertNotEquals(
            false,
            \crisp\api\Phoenix::getServicePG(1)
    );
    $this->assertNotEquals(
            false,
            \crisp\api\Phoenix::getServiceByNamePG("Test Service")
    );
    $this->assertNotEquals(
            false,
            \crisp\api\Phoenix::getServiceBySlugPG("test_slug")
    );
  }

}
