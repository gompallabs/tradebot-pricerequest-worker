<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\Store\Adapter\RedisTimeSeries\ClientInterface;
use App\Domain\Store\Adapter\RedisTimeSeries\Vo\ConnectionParams;
use App\Infra\Store\Adapter\RedisTimeSeries\Client;
use PHPUnit\Framework\TestCase;

use function PHPUnit\Framework\assertFalse;

class ItCreatesClientTest extends TestCase
{
    public function testItCreatesClient()
    {
        $connectionParams = new ConnectionParams(
            getenv('REDIS_HOST'),
            (int) getenv('REDIS_PORT')
        );
        $client = new Client(
            new \Redis(),
            $connectionParams
        );
        self::assertInstanceOf(ClientInterface::class, $client);
    }

    public function testItWritesKey()
    {
        $connectionParams = new ConnectionParams(
            getenv('REDIS_HOST'),
            (int) getenv('REDIS_PORT')
        );
        $client = new Client(
            new \Redis(),
            $connectionParams
        );
        $value = uniqid(random_bytes(12));
        $client->set('test', $value, null);
        $stored = $client->get('test');
        self::assertEquals($value, $stored);
    }

    public function testItDeletesKey()
    {
        $connectionParams = new ConnectionParams(
            getenv('REDIS_HOST'),
            (int) getenv('REDIS_PORT')
        );
        $client = new Client(
            new \Redis(),
            $connectionParams
        );
        $stored = $client->get('test');
        self::assertNotFalse($stored);
        self::assertIsString($stored);
        $client->unlink('test');
        $stored = $client->get('test');
        assertFalse($stored);
    }
}
