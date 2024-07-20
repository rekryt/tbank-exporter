<?php

namespace TBank\App\Service;

use TBank\AsyncTest;
use TBank\Infrastructure\API\App;
use TBank\Infrastructure\Storage\MainStorage;

/**
 * @runInSeparateProcess
 */
final class OperationsServiceTest extends AsyncTest {
    protected function setUp(): void {
        parent::setUp();
        $this->service = new OperationsService();
    }

    /**
     * @return void
     * @covers \TBank\App\Service\OperationsService::getPortfolio
     */
    public function testGetPortfolio(): void {
        $this->assertIsNumeric(MainStorage::getInstance()->getPortfolio()->accountId);
    }
}
