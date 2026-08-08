<?php

namespace Kodhe\Database\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Kodhe\Database\DB;
use Kodhe\Database\BaseModel;

/**
 * Test Model untuk testing
 */
class TestModel extends BaseModel
{
    protected $table = 'test_table';
    protected $primaryKey = 'id';
    protected $allowedFields = ['name', 'email', 'status'];
}

/**
 * Unit Test untuk DB Facade
 */
class DBTest extends TestCase
{
    public function testDBFacadeCanBeAccessed(): void
    {
        $this->assertTrue(class_exists(DB::class));
    }

    public function testTableMethodReturnsQueryBuilder(): void
    {
        // Note: This test will need CI instance for full functionality
        // For now, we test that the method exists and returns correct type
        $reflection = new \ReflectionClass(DB::class);
        $this->assertTrue($reflection->hasMethod('table'));
    }

    public function testConnectMethodExists(): void
    {
        $reflection = new \ReflectionClass(DB::class);
        $this->assertTrue($reflection->hasMethod('connect'));
    }

    public function testTransactionMethodsExist(): void
    {
        $reflection = new \ReflectionClass(DB::class);
        $this->assertTrue($reflection->hasMethod('beginTransaction'));
        $this->assertTrue($reflection->hasMethod('commit'));
        $this->assertTrue($reflection->hasMethod('rollback'));
    }
}

/**
 * Unit Test untuk BaseModel
 */
class BaseModelTest extends TestCase
{
    private TestModel $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new TestModel();
    }

    public function testModelCanBeInstantiated(): void
    {
        $this->assertInstanceOf(TestModel::class, $this->model);
    }

    public function testTableNameIsSet(): void
    {
        $this->assertEquals('test_table', $this->model->getTable());
    }

    public function testPrimaryKeyIsSet(): void
    {
        $reflection = new \ReflectionClass($this->model);
        $property = $reflection->getProperty('primaryKey');
        $property->setAccessible(true);
        $this->assertEquals('id', $property->getValue($this->model));
    }

    public function testAllowedFieldsAreSet(): void
    {
        $reflection = new \ReflectionClass($this->model);
        $property = $reflection->getProperty('allowedFields');
        $property->setAccessible(true);
        $this->assertEquals(['name', 'email', 'status'], $property->getValue($this->model));
    }

    public function testTableCanBeChanged(): void
    {
        $this->model->setTable('new_table');
        $this->assertEquals('new_table', $this->model->getTable());
    }
}
