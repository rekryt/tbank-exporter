<?php

namespace TBank\App\Service;

use TBank\AsyncTest;
use TBank\Infrastructure\Storage\MainStorage;

/**
 * @runInSeparateProcess
 */
final class InstrumentsServiceTest extends AsyncTest {
    protected function setUp(): void {
        parent::setUp();
        $this->service = new InstrumentsService();
    }

    /**
     * @return void
     * @covers \TBank\App\Service\InstrumentsService::findInstrument
     */
    public function testFindInstrument(): void {
        $this->assertGreaterThan(0, count(MainStorage::getInstance()->getTickers()));
    }
}
