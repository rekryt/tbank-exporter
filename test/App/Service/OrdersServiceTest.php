<?php

namespace TBank\App\Service;

use TBank\AsyncTest;
use TBank\Infrastructure\Storage\MainStorage;

use Amp\ByteStream\BufferException;
use Amp\ByteStream\StreamException;
use Amp\Http\Client\HttpException;

/**
 * @runInSeparateProcess
 */
final class OrdersServiceTest extends AsyncTest {
    protected function setUp(): void {
        parent::setUp();
        $this->service = new OrdersService();
    }

    /**
     * @return void
     * @throws BufferException
     * @throws StreamException
     * @throws HttpException
     * @covers \TBank\App\Service\OrdersService::getOrders
     */
    public function testGetOrders(): void {
        $this->assertIsArray($this->service->getOrders(MainStorage::getInstance()->getAccount()->id));
    }
}
