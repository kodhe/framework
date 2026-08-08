<?php

namespace Kodhe\Database\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Kodhe\Database\Connections\Connection;
use Kodhe\Database\Builders\QueryBuilder;
use Kodhe\Database\BaseModel;

/**
 * Unit Test untuk Connection
 */
class ConnectionTest extends TestCase
{
    private Connection $connection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->connection = new Connection();
    }

    public function testConnectionCanBeInstantiated(): void
    {
        $this->assertInstanceOf(Connection::class, $this->connection);
    }

    public function testConfigCanBeSet(): void
    {
        $config = [
            'hostname' => 'localhost',
            'database' => 'test_db',
            'username' => 'test_user',
            'password' => 'test_pass',
        ];

        $connection = new Connection($config);
        $this->assertEquals($config, $connection->getConfig());
    }

    public function testDriverCanBeSet(): void
    {
        $this->connection->setDriver('pdo');
        $this->assertEquals('pdo', $this->connection->getDriver());
    }

    public function testDefaultDriverIsMysqli(): void
    {
        $this->assertEquals('mysqli', $this->connection->getDriver());
    }
}
