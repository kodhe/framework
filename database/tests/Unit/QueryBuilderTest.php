<?php

namespace Kodhe\Database\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Kodhe\Database\Builders\QueryBuilder;

/**
 * Unit Test untuk QueryBuilder
 */
class QueryBuilderTest extends TestCase
{
    private QueryBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->builder = new QueryBuilder('users');
    }

    public function testQueryBuilderCanBeInstantiated(): void
    {
        $this->assertInstanceOf(QueryBuilder::class, $this->builder);
    }

    public function testTableCanBeSet(): void
    {
        $builder = new QueryBuilder();
        $builder->from('products');
        $this->assertEquals('products', $builder->getTable());
    }

    public function testSelectCanBeSet(): void
    {
        $this->builder->select(['id', 'name', 'email']);
        $sql = $this->builder->toSql();
        $this->assertStringContainsString('SELECT id, name, email', $sql);
    }

    public function testWhereCanBeAdded(): void
    {
        $this->builder->where('status', '=', 'active');
        $sql = $this->builder->toSql();
        $this->assertStringContainsString('WHERE', $sql);
        $this->assertStringContainsString('status', $sql);
    }

    public function testLimitCanBeSet(): void
    {
        $this->builder->limit(10, 5);
        $sql = $this->builder->toSql();
        $this->assertStringContainsString('LIMIT 10', $sql);
    }

    public function testOrderByCanBeSet(): void
    {
        $this->builder->orderBy('created_at', 'DESC');
        $sql = $this->builder->toSql();
        $this->assertStringContainsString('ORDER BY', $sql);
        $this->assertStringContainsString('DESC', $sql);
    }

    public function testJoinCanBeAdded(): void
    {
        $this->builder->join('posts', 'users.id', '=', 'posts.user_id', 'left');
        $sql = $this->builder->toSql();
        $this->assertStringContainsString('LEFT JOIN', $sql);
        $this->assertStringContainsString('posts', $sql);
    }

    public function testStaticTableMethod(): void
    {
        $builder = QueryBuilder::table('orders');
        $this->assertEquals('orders', $builder->getTable());
    }
}
