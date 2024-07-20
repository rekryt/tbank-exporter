<?php

namespace TBank\App\Service;

use TBank\AsyncTest;
use TBank\Infrastructure\API\App;
use TBank\Infrastructure\Storage\MainStorage;

use Amp\ByteStream\BufferException;
use Amp\ByteStream\StreamException;
use Amp\Http\Client\HttpException;
use Amp\PHPUnit\AsyncTestCase;

/**
 * @runInSeparateProcess
 */
final class UsersServiceTest extends AsyncTest {
    protected function setUp(): void {
        parent::setUp();
        $this->service = new UsersService();
    }

    /**
     * @return void
     * @throws BufferException
     * @throws StreamException
     * @throws HttpException
     * @covers \TBank\App\Service\UsersService::getAccounts
     */
    public function testGetAccounts(): void {
        $this->assertIsArray($this->service->getAccounts());
        $this->assertIsNumeric(MainStorage::getInstance()->getAccount()->id);
    }
}
