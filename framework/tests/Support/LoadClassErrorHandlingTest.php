<?php

declare(strict_types=1);

namespace Kodhe\Framework\Tests\Support;

use PHPUnit\Framework\TestCase;
use Kodhe\Framework\Exceptions\ClassLoadingException;
use Kodhe\Framework\Exceptions\BaseException;

/**
 * Regression tests for Stage 3: load_class() error handling.
 *
 * Before the fix, a missing class triggered the legacy hard-fail
 * (set_status_header(503) + echo + exit(5)), killing the PHP process.
 * A failed namespaced autoload silently returned false from a
 * by-reference function. Both now throw a catchable
 * ClassLoadingException carrying HTTP 503 metadata.
 *
 * @runInSeparateProcess
 * @preserveGlobalState disabled
 */
class LoadClassErrorHandlingTest extends TestCase
{
    /**
     * Minimal CI3-ish constant environment required by common.php / Helpers.php.
     */
    private function bootstrapConstants(): void
    {
        $tmp = sys_get_temp_dir() . '/kodhe_loadclass_test_' . uniqid();
        @mkdir($tmp . '/system', 0777, true);
        @mkdir($tmp . '/application/libraries', 0777, true);
        @mkdir($tmp . '/storage/cache', 0777, true);

        if (!defined('ENVIRONMENT')) define('ENVIRONMENT', 'testing');
        if (!defined('BASEPATH'))   define('BASEPATH', $tmp . '/system/');
        if (!defined('APPPATH'))    define('APPPATH', $tmp . '/application/');
        if (!defined('FCPATH'))     define('FCPATH', $tmp . '/public/');
        if (!defined('ROOTPATH'))   define('ROOTPATH', $tmp . '/');
        if (!defined('STORAGEPATH')) define('STORAGEPATH', $tmp . '/storage/');
        if (!defined('EXIT_UNK_CLASS')) define('EXIT_UNK_CLASS', 5);

        // config_item() is used by load_class(); provide a stub if absent.
        if (!function_exists('config_item')) {
            eval('function config_item($k = "", $d = FALSE) { return $k === "subclass_prefix" ? "MY_" : $d; }');
        }
        if (!function_exists('set_status_header')) {
            eval('function set_status_header($c = 200, $m = "") { return TRUE; }');
        }
    }

    public function testExceptionCarriesHttp503AndErrorCode(): void
    {
        $e = ClassLoadingException::unableToLocate('Ghost', 'libraries');

        $this->assertInstanceOf(BaseException::class, $e);
        $this->assertSame(503, $e->getHttpStatusCode());
        $this->assertSame('CLASS_LOAD_ERROR', $e->getErrorCode());
        $this->assertSame('Ghost', $e->getRequestedClass());
        $this->assertStringContainsString('Unable to locate the specified class: Ghost.php', $e->getMessage());
        $this->assertSame('libraries', $e->getData()['directory']);
    }

    public function testUnableToAutoloadFactory(): void
    {
        $e = ClassLoadingException::unableToAutoload('Acme\\Missing\\Klass');

        $this->assertStringContainsString('Unable to autoload the specified class: Acme\\Missing\\Klass', $e->getMessage());
        $this->assertSame('Acme\\Missing\\Klass', $e->getRequestedClass());
    }

    public function testLoadClassThrowsInsteadOfExitingForMissingLegacyClass(): void
    {
        $this->bootstrapConstants();
        require_once __DIR__ . '/../../src/Support/Legacy/common.php';
        require_once __DIR__ . '/../../src/Support/Helpers.php';

        // Must be catchable — previously this line killed the process via exit(5).
        try {
            $obj = load_class('DefinitelyMissingClassXYZ', 'libraries');
            $this->fail('Expected ClassLoadingException was not thrown (got ' . var_export($obj, true) . ')');
        } catch (ClassLoadingException $e) {
            $this->assertSame('DefinitelyMissingClassXYZ', $e->getRequestedClass());
        }
    }

    public function testLoadClassThrowsForUnknownNamespacedClass(): void
    {
        $this->bootstrapConstants();
        require_once __DIR__ . '/../../src/Support/Legacy/common.php';
        require_once __DIR__ . '/../../src/Support/Helpers.php';

        // Previously: silently `return false` from a by-reference function
        // (plus an "Only variable references should be returned by reference"
        // notice). Now: a catchable exception.
        $this->expectException(ClassLoadingException::class);
        load_class('Totally\\Not\\Existing\\Klass');
    }

    public function testSuccessfulLoadStillWorks(): void
    {
        $this->bootstrapConstants();
        require_once __DIR__ . '/../../src/Support/Legacy/common.php';
        require_once __DIR__ . '/../../src/Support/Helpers.php';

        // Create a discoverable CI_ library class on the fly.
        $file = rtrim(APPPATH, '/') . '/libraries/Teststage3.php';
        file_put_contents($file, "<?php\nif (!class_exists('CI_Teststage3', false)) { class CI_Teststage3 { public \$ok = TRUE; } }\n");

        $instance = load_class('Teststage3', 'libraries');
        $this->assertInstanceOf('CI_Teststage3', $instance);
        $this->assertTrue($instance->ok);

        // Singleton behavior preserved.
        $this->assertSame($instance, load_class('Teststage3', 'libraries'));

        @unlink($file);
    }
}
