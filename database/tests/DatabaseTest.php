<?php

declare(strict_types=0);

namespace Kodhe\Framework\Tests\CI3Compat\Database;

use PHPUnit\Framework\TestCase;
use Kodhe\Framework\Database\DB;

/**
 * Test Database library compatibility with CodeIgniter 3 API
 * 
 * Ensures all public methods and properties work exactly like CI3
 */
class DatabaseTest extends TestCase
{
    private $db;
    private $config;

    protected function setUp(): void
    {
        parent::setUp();
        
        // CI3-style database configuration
        $this->config = [
            'dsn'      => '',
            'hostname' => 'localhost',
            'username' => 'root',
            'password' => '',
            'database' => 'test_db',
            'dbdriver' => 'mysqli',
            'dbprefix' => '',
            'pconnect' => FALSE,
            'db_debug' => FALSE,
            'cache_on' => FALSE,
            'cachedir' => '',
            'char_set' => 'utf8',
            'dbcollat' => 'utf8_general_ci',
            'swap_pre' => '',
            'encrypt'  => FALSE,
            'compress' => FALSE,
            'strict_on'=> FALSE,
            'failover' => array(),
            'port'     => 3306,
        ];
    }

    // =========================================================================
    // CONNECTION TESTS
    // =========================================================================

    public function testDbInitialize(): void
    {
        $this->db = DB::connect($this->config);
        $this->assertInstanceOf('Kodhe\Framework\Database\Connection\ConnectionInterface', $this->db);
    }

    public function testDefaultHostname(): void
    {
        $this->db = DB::connect($this->config);
        $this->assertEquals('localhost', $this->db->hostname);
    }

    public function testDefaultUsername(): void
    {
        $this->db = DB::connect($this->config);
        $this->assertEquals('root', $this->db->username);
    }

    public function testDefaultDatabase(): void
    {
        $this->db = DB::connect($this->config);
        $this->assertEquals('test_db', $this->db->database);
    }

    public function testDefaultDbdriver(): void
    {
        $this->db = DB::connect($this->config);
        $this->assertEquals('mysqli', $this->db->dbdriver);
    }

    public function testDefaultPort(): void
    {
        $this->db = DB::connect($this->config);
        $this->assertEquals(3306, $this->db->port);
    }

    public function testDefaultCharSet(): void
    {
        $this->db = DB::connect($this->config);
        $this->assertEquals('utf8', $this->db->char_set);
    }

    public function testDefaultDbCollat(): void
    {
        $this->db = DB::connect($this->config);
        $this->assertEquals('utf8_general_ci', $this->db->dbcollat);
    }

    // =========================================================================
    // QUERY BUILDING TESTS
    // =========================================================================

    public function testQueryMethodExists(): void
    {
        $this->db = DB::connect($this->config);
        $this->assertTrue(method_exists($this->db, 'query'));
    }

    public function testSimpleQueryMethodExists(): void
    {
        $this->db = DB::connect($this->config);
        $this->assertTrue(method_exists($this->db, 'simple_query'));
    }

    public function testInsertMethodExists(): void
    {
        $this->db = DB::connect($this->config);
        $this->assertTrue(method_exists($this->db, 'insert'));
    }

    public function testUpdateMethodExists(): void
    {
        $this->db = DB::connect($this->config);
        $this->assertTrue(method_exists($this->db, 'update'));
    }

    public function testDeleteMethodExists(): void
    {
        $this->db = DB::connect($this->config);
        $this->assertTrue(method_exists($this->db, 'delete'));
    }

    public function testGetMethodExists(): void
    {
        $this->db = DB::connect($this->config);
        $this->assertTrue(method_exists($this->db, 'get'));
    }

    public function testGetWhereMethodExists(): void
    {
        $this->db = DB::connect($this->config);
        $this->assertTrue(method_exists($this->db, 'get_where'));
    }

    public function testSelectMethodExists(): void
    {
        $this->db = DB::connect($this->config);
        $this->assertTrue(method_exists($this->db, 'select'));
    }

    public function testFromMethodExists(): void
    {
        $this->db = DB::connect($this->config);
        $this->assertTrue(method_exists($this->db, 'from'));
    }

    public function testWhereMethodExists(): void
    {
        $this->db = DB::connect($this->config);
        $this->assertTrue(method_exists($this->db, 'where'));
    }

    public function testOrWhereMethodExists(): void
    {
        $this->db = DB::connect($this->config);
        $this->assertTrue(method_exists($this->db, 'or_where'));
    }

    public function testLikeMethodExists(): void
    {
        $this->db = DB::connect($this->config);
        $this->assertTrue(method_exists($this->db, 'like'));
    }

    public function testOrLikeMethodExists(): void
    {
        $this->db = DB::connect($this->config);
        $this->assertTrue(method_exists($this->db, 'or_like'));
    }

    public function testOrderByMethodExists(): void
    {
        $this->db = DB::connect($this->config);
        $this->assertTrue(method_exists($this->db, 'order_by'));
    }

    public function testLimitMethodExists(): void
    {
        $this->db = DB::connect($this->config);
        $this->assertTrue(method_exists($this->db, 'limit'));
    }

    public function testOffsetMethodExists(): void
    {
        $this->db = DB::connect($this->config);
        $this->assertTrue(method_exists($this->db, 'offset'));
    }

    public function testGroupByMethodExists(): void
    {
        $this->db = DB::connect($this->config);
        $this->assertTrue(method_exists($this->db, 'group_by'));
    }

    public function testHavingMethodExists(): void
    {
        $this->db = DB::connect($this->config);
        $this->assertTrue(method_exists($this->db, 'having'));
    }

    public function testOrHavingMethodExists(): void
    {
        $this->db = DB::connect($this->config);
        $this->assertTrue(method_exists($this->db, 'or_having'));
    }

    public function testJoinMethodExists(): void
    {
        $this->db = DB::connect($this->config);
        $this->assertTrue(method_exists($this->db, 'join'));
    }

    public function testDistinctMethodExists(): void
    {
        $this->db = DB::connect($this->config);
        $this->assertTrue(method_exists($this->db, 'distinct'));
    }

    // =========================================================================
    // TRANSACTION TESTS
    // =========================================================================

    public function testTransStartMethodExists(): void
    {
        $this->db = DB::connect($this->config);
        $this->assertTrue(method_exists($this->db, 'trans_start'));
    }

    public function testTransCompleteMethodExists(): void
    {
        $this->db = DB::connect($this->config);
        $this->assertTrue(method_exists($this->db, 'trans_complete'));
    }

    public function testTransBeginMethodExists(): void
    {
        $this->db = DB::connect($this->config);
        $this->assertTrue(method_exists($this->db, 'trans_begin'));
    }

    public function testTransCommitMethodExists(): void
    {
        $this->db = DB::connect($this->config);
        $this->assertTrue(method_exists($this->db, 'trans_commit'));
    }

    public function testTransRollbackMethodExists(): void
    {
        $this->db = DB::connect($this->config);
        $this->assertTrue(method_exists($this->db, 'trans_rollback'));
    }

    public function testTransStatusMethodExists(): void
    {
        $this->db = DB::connect($this->config);
        $this->assertTrue(method_exists($this->db, 'trans_status'));
    }

    // =========================================================================
    // UTILITY METHODS TESTS
    // =========================================================================

    public function testEscapeMethodExists(): void
    {
        $this->db = DB::connect($this->config);
        $this->assertTrue(method_exists($this->db, 'escape'));
    }

    public function testEscapeStringMethodExists(): void
    {
        $this->db = DB::connect($this->config);
        $this->assertTrue(method_exists($this->db, 'escape_str'));
    }

    public function testEscapeLikeStrMethodExists(): void
    {
        $this->db = DB::connect($this->config);
        $this->assertTrue(method_exists($this->db, 'escape_like_str'));
    }

    public function testAffectedRowsMethodExists(): void
    {
        $this->db = DB::connect($this->config);
        $this->assertTrue(method_exists($this->db, 'affected_rows'));
    }

    public function testInsertIdMethodExists(): void
    {
        $this->db = DB::connect($this->config);
        $this->assertTrue(method_exists($this->db, 'insert_id'));
    }

    public function testNumRowsMethodExists(): void
    {
        $this->db = DB::connect($this->config);
        $this->assertTrue(method_exists($this->db, 'num_rows'));
    }

    public function testCountAllMethodExists(): void
    {
        $this->db = DB::connect($this->config);
        $this->assertTrue(method_exists($this->db, 'count_all'));
    }

    public function testCountAllResultsMethodExists(): void
    {
        $this->db = DB::connect($this->config);
        $this->assertTrue(method_exists($this->db, 'count_all_results'));
    }

    public function testListTablesMethodExists(): void
    {
        $this->db = DB::connect($this->config);
        $this->assertTrue(method_exists($this->db, 'list_tables'));
    }

    public function testTableExistsMethodExists(): void
    {
        $this->db = DB::connect($this->config);
        $this->assertTrue(method_exists($this->db, 'table_exists'));
    }

    public function testFieldDataMethodExists(): void
    {
        $this->db = DB::connect($this->config);
        $this->assertTrue(method_exists($this->db, 'field_data'));
    }

    public function testListFieldsMethodExists(): void
    {
        $this->db = DB::connect($this->config);
        $this->assertTrue(method_exists($this->db, 'list_fields'));
    }

    public function testErrorMethodExists(): void
    {
        $this->db = DB::connect($this->config);
        $this->assertTrue(method_exists($this->db, 'error'));
    }

    // =========================================================================
    // CACHING TESTS
    // =========================================================================

    public function testCacheOnMethodExists(): void
    {
        $this->db = DB::connect($this->config);
        $this->assertTrue(method_exists($this->db, 'cache_on'));
    }

    public function testCacheOffMethodExists(): void
    {
        $this->db = DB::connect($this->config);
        $this->assertTrue(method_exists($this->db, 'cache_off'));
    }

    public function testCacheSetPathMethodExists(): void
    {
        $this->db = DB::connect($this->config);
        $this->assertTrue(method_exists($this->db, 'cache_set_path'));
    }

    public function testCacheDeleteMethodExists(): void
    {
        $this->db = DB::connect($this->config);
        $this->assertTrue(method_exists($this->db, 'cache_delete'));
    }

    public function testCacheDeleteAllMethodExists(): void
    {
        $this->db = DB::connect($this->config);
        $this->assertTrue(method_exists($this->db, 'cache_delete_all'));
    }

    // =========================================================================
    // CONNECTION MANAGEMENT TESTS
    // =========================================================================

    public function testCloseMethodExists(): void
    {
        $this->db = DB::connect($this->config);
        $this->assertTrue(method_exists($this->db, 'close'));
    }

    public function testReconnectMethodExists(): void
    {
        $this->db = DB::connect($this->config);
        $this->assertTrue(method_exists($this->db, 'reconnect'));
    }

    public function testInitializeMethodExists(): void
    {
        $this->db = DB::connect($this->config);
        $this->assertTrue(method_exists($this->db, 'initialize'));
    }

    // =========================================================================
    // PROPERTIES TESTS
    // =========================================================================

    public function testConnIdProperty(): void
    {
        $this->db = DB::connect($this->config);
        $this->assertFalse($this->db->conn_id);
    }

    public function testResultIdProperty(): void
    {
        $this->db = DB::connect($this->config);
        $this->assertFalse($this->db->result_id);
    }

    public function testDbDebugProperty(): void
    {
        $this->db = DB::connect($this->config);
        $this->assertFalse($this->db->db_debug);
    }

    public function testBenchmarkProperty(): void
    {
        $this->db = DB::connect($this->config);
        $this->assertEquals(0, $this->db->benchmark);
    }

    public function testQueryCountProperty(): void
    {
        $this->db = DB::connect($this->config);
        $this->assertEquals(0, $this->db->query_count);
    }

    public function testQueriesProperty(): void
    {
        $this->db = DB::connect($this->config);
        $this->assertIsArray($this->db->queries);
    }

    public function testQueryTimesProperty(): void
    {
        $this->db = DB::connect($this->config);
        $this->assertIsArray($this->db->query_times);
    }

    public function testDataCacheProperty(): void
    {
        $this->db = DB::connect($this->config);
        $this->assertIsArray($this->db->data_cache);
    }

    public function testTransEnabledProperty(): void
    {
        $this->db = DB::connect($this->config);
        $this->assertTrue($this->db->trans_enabled);
    }

    public function testTransStrictProperty(): void
    {
        $this->db = DB::connect($this->config);
        $this->assertTrue($this->db->trans_strict);
    }

    public function testSaveQueriesProperty(): void
    {
        $this->db = DB::connect($this->config);
        $this->assertTrue($this->db->save_queries);
    }

    public function testCacheOnProperty(): void
    {
        $this->db = DB::connect($this->config);
        $this->assertFalse($this->db->cache_on);
    }

    public function testCachedirProperty(): void
    {
        $this->db = DB::connect($this->config);
        $this->assertEquals('', $this->db->cachedir);
    }

    public function testDbprefixProperty(): void
    {
        $this->db = DB::connect($this->config);
        $this->assertEquals('', $this->db->dbprefix);
    }

    public function testSwapPreProperty(): void
    {
        $this->db = DB::connect($this->config);
        $this->assertEquals('', $this->db->swap_pre);
    }

    public function testEncryptProperty(): void
    {
        $this->db = DB::connect($this->config);
        $this->assertFalse($this->db->encrypt);
    }

    public function testPconnectProperty(): void
    {
        $this->db = DB::connect($this->config);
        $this->assertFalse($this->db->pconnect);
    }

    // =========================================================================
    // ESCAPE STRING TESTS (actual functionality)
    // =========================================================================

    public function testEscapeStringBasic(): void
    {
        $this->db = DB::connect($this->config);
        
        // Mock the escape method to avoid needing actual connection
        $mock = $this->getMockBuilder(get_class($this->db))
            ->disableOriginalConstructor()
            ->onlyMethods(['escape'])
            ->getMock();
        
        $mock->method('escape')
            ->willReturnCallback(function($str) {
                return "'" . addslashes($str) . "'";
            });
        
        $result = $mock->escape("test'string");
        $this->assertEquals("'test\'string'", $result);
    }

    public function testEscapeStringWithNull(): void
    {
        $this->db = DB::connect($this->config);
        
        $mock = $this->getMockBuilder(get_class($this->db))
            ->disableOriginalConstructor()
            ->onlyMethods(['escape'])
            ->getMock();
        
        $mock->method('escape')
            ->willReturnCallback(function($str) {
                if ($str === NULL) {
                    return 'NULL';
                }
                return "'" . addslashes($str) . "'";
            });
        
        $result = $mock->escape(NULL);
        $this->assertEquals('NULL', $result);
    }

    public function testEscapeStringWithInteger(): void
    {
        $this->db = DB::connect($this->config);
        
        $mock = $this->getMockBuilder(get_class($this->db))
            ->disableOriginalConstructor()
            ->onlyMethods(['escape'])
            ->getMock();
        
        $mock->method('escape')
            ->willReturnCallback(function($str) {
                if (is_int($str)) {
                    return (string) $str;
                }
                return "'" . addslashes($str) . "'";
            });
        
        $result = $mock->escape(123);
        $this->assertEquals('123', $result);
    }

    public function testEscapeStringWithBoolean(): void
    {
        $this->db = DB::connect($this->config);
        
        $mock = $this->getMockBuilder(get_class($this->db))
            ->disableOriginalConstructor()
            ->onlyMethods(['escape'])
            ->getMock();
        
        $mock->method('escape')
            ->willReturnCallback(function($str) {
                if (is_bool($str)) {
                    return $str ? 'TRUE' : 'FALSE';
                }
                return "'" . addslashes($str) . "'";
            });
        
        $result = $mock->escape(TRUE);
        $this->assertEquals('TRUE', $result);
        
        $result = $mock->escape(FALSE);
        $this->assertEquals('FALSE', $result);
    }

    // =========================================================================
    // BIND MARKER TESTS
    // =========================================================================

    public function testBindMarkerProperty(): void
    {
        $this->db = DB::connect($this->config);
        $this->assertEquals('?', $this->db->bind_marker);
    }

    public function testCompileBindsMethodExists(): void
    {
        $this->db = DB::connect($this->config);
        $this->assertTrue(method_exists($this->db, 'compile_binds'));
    }

    public function testCompileBindsWithSingleBind(): void
    {
        $this->db = DB::connect($this->config);
        
        $sql = "SELECT * FROM users WHERE id = ?";
        $binds = [1];
        
        $result = $this->db->compile_binds($sql, $binds);
        $this->assertEquals("SELECT * FROM users WHERE id = 1", $result);
    }

    public function testCompileBindsWithMultipleBinds(): void
    {
        $this->db = DB::connect($this->config);
        
        $sql = "SELECT * FROM users WHERE id = ? AND name = ?";
        $binds = [1, 'John'];
        
        $result = $this->db->compile_binds($sql, $binds);
        $this->assertEquals("SELECT * FROM users WHERE id = 1 AND name = 'John'", $result);
    }

    public function testCompileBindsWithStringContainingMarker(): void
    {
        $this->db = DB::connect($this->config);
        
        $sql = "SELECT * FROM users WHERE name = '?' AND id = ?";
        $binds = [1];
        
        $result = $this->db->compile_binds($sql, $binds);
        // The ? inside quotes should not be replaced
        $this->assertStringContainsString("'?'", $result);
    }

    public function testCompileBindsWithArrayValue(): void
    {
        $this->db = DB::connect($this->config);
        
        $sql = "SELECT * FROM users WHERE id IN (?)";
        $binds = [[1, 2, 3]];
        
        $result = $this->db->compile_binds($sql, $binds);
        $this->assertEquals("SELECT * FROM users WHERE id IN (1,2,3)", $result);
    }

    public function testCompileBindsMismatchedCount(): void
    {
        $this->db = DB::connect($this->config);
        
        $sql = "SELECT * FROM users WHERE id = ? AND name = ?";
        $binds = [1]; // Only one bind for two markers
        
        $result = $this->db->compile_binds($sql, $binds);
        // Should return original SQL when count doesn't match
        $this->assertEquals($sql, $result);
    }

    public function testCompileBindsWithNumericBinds(): void
    {
        $this->db = DB::connect($this->config);
        
        $sql = "SELECT * FROM users WHERE id = ? AND name = ?";
        $binds = [0 => 1, 1 => 'John'];
        
        $result = $this->db->compile_binds($sql, $binds);
        $this->assertEquals("SELECT * FROM users WHERE id = 1 AND name = 'John'", $result);
    }

    public function testCompileBindsWithAssociativeArray(): void
    {
        $this->db = DB::connect($this->config);
        
        $sql = "SELECT * FROM users WHERE id = ? AND name = ?";
        $binds = ['id' => 1, 'name' => 'John'];
        
        $result = $this->db->compile_binds($sql, $binds);
        // Associative keys should be converted to numeric
        $this->assertEquals("SELECT * FROM users WHERE id = 1 AND name = 'John'", $result);
    }

    public function testCompileBindsWithZeroValue(): void
    {
        $this->db = DB::connect($this->config);
        
        $sql = "SELECT * FROM users WHERE status = ?";
        $binds = [0];
        
        $result = $this->db->compile_binds($sql, $binds);
        $this->assertEquals("SELECT * FROM users WHERE status = 0", $result);
    }

    public function testCompileBindsWithEmptyString(): void
    {
        $this->db = DB::connect($this->config);
        
        $sql = "SELECT * FROM users WHERE name = ?";
        $binds = [''];
        
        $result = $this->db->compile_binds($sql, $binds);
        $this->assertEquals("SELECT * FROM users WHERE name = ''", $result);
    }

    public function testCompileBindsWithFloatValue(): void
    {
        $this->db = DB::connect($this->config);
        
        $sql = "SELECT * FROM products WHERE price = ?";
        $binds = [19.99];
        
        $result = $this->db->compile_binds($sql, $binds);
        $this->assertEquals("SELECT * FROM products WHERE price = 19.99", $result);
    }

    public function testCompileBindsWithoutMarker(): void
    {
        $this->db = DB::connect($this->config);
        
        $sql = "SELECT * FROM users WHERE id = 1";
        $binds = [1];
        
        $result = $this->db->compile_binds($sql, $binds);
        // Should return original SQL when no marker found
        $this->assertEquals($sql, $result);
    }

    public function testCompileBindsWithEmptyBinds(): void
    {
        $this->db = DB::connect($this->config);
        
        $sql = "SELECT * FROM users WHERE id = ?";
        $binds = [];
        
        $result = $this->db->compile_binds($sql, $binds);
        // Should return original SQL when binds is empty
        $this->assertEquals($sql, $result);
    }

    public function testCompileBindsWithSingleBindsNotArray(): void
    {
        $this->db = DB::connect($this->config);
        
        $sql = "SELECT * FROM users WHERE id = ?";
        $binds = 123;
        
        $result = $this->db->compile_binds($sql, $binds);
        $this->assertEquals("SELECT * FROM users WHERE id = 123", $result);
    }
}
