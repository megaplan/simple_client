<?php

namespace Megaplan\SimpleClient\Tests\Functional;

use Megaplan\SimpleClient\Client;

class SimpleTest extends \PHPUnit_Framework_TestCase
{
    const HOST = 'megaplan.local';
    const LOGIN = 'dev-null@megoplan.ru';
    const PASSWORD = '123';

    public function testGetJSON()
    {
        $response = $this->client()->get('/BumsTaskApiV01/Task/list.api');
        self::assertInternalType('object', $response);
        self::assertObjectHasAttribute('status', $response);
        self::assertObjectHasAttribute('data', $response);
    }

    /**
     * @return Client
     */
    protected function client()
    {
        $request = new Client(self::HOST);

        return $request->useHttps(false)
            ->auth(self::LOGIN, self::PASSWORD);
    }
}
