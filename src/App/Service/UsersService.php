<?php

namespace TBank\App\Service;

use TBank\Domain\Factory\AccountFactory;
use TBank\Infrastructure\API\App;
use TBank\Infrastructure\Storage\MainStorage;

use Amp\Http\Client\HttpException;
use Amp\ByteStream\BufferException;
use Amp\ByteStream\StreamException;

use Monolog\Logger;
use Throwable;

use function TBank\getEnv;

final class UsersService extends AbstractRestService {
    private string $path = '/rest/tinkoff.public.invest.api.contract.v1.UsersService/';
    private MainStorage $storage;
    private Logger $logger;

    /**
     * @throws Throwable
     */
    public function __construct() {
        $this->logger = App::getLogger()->withName('UsersService');
        parent::__construct($this->logger);

        $this->storage = MainStorage::getInstance();
        $accounts = $this->getAccounts();
        if (count($accounts) == 0) {
            throw new \Exception('Invalid account');
        }
        foreach ($accounts as $accountData) {
            $account = AccountFactory::create($accountData);
            if ($account->status != 'ACCOUNT_STATUS_OPEN') {
                continue;
            }
            if (!getEnv('API_ACCOUNT') || getEnv('API_ACCOUNT') == $account->id) {
                $this->storage->setAccount($account);
                break;
            }
        }
    }

    /**
     * @return array|false
     * @throws BufferException
     * @throws HttpException
     * @throws StreamException
     * @see UsersServiceTest::testGetAccounts()
     */
    public function getAccounts(): array|false {
        $response = $this->httpRequest($this->path . 'GetAccounts', ['status' => 'ACCOUNT_STATUS_OPEN']);
        return $response ? $response->accounts ?? false : false;
    }
}
