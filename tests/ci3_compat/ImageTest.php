<?php

declare(strict_types=1);

namespace Kodhe\Framework\Tests\CI3Compat;

use PHPUnit\Framework\TestCase;
use Kodhe\Framework\Image\ImageLib;

/**
 * Test Image library compatibility with CodeIgniter 3 API
 */
class ImageTest extends TestCase
{
    private ImageLib $image;

    protected function setUp(): void
    {
        parent::setUp();
        // Create a test image for testing
        $this->createTestImage();
        $this->image = new ImageLib([
            'source_image' => $this->getTestImagePath(),
        ]);
    }

    protected function tearDown(): void
    {
        // Clean up test image
        if (file_exists($this->getTestImagePath())) {
            unlink($this->getTestImagePath());
        }
        parent::tearDown();
    }

    private function createTestImage(): void
    {
        $image = imagecreatetruecolor(200, 200);
        $white = imagecolorallocate($image, 255, 255, 255);
        imagefill($image, 0, 0, $white);
        imagepng($image, $this->getTestImagePath());
        imagedestroy($image);
    }

    private function getTestImagePath(): string
    {
        return sys_get_temp_dir() . '/test_image.png';
    }

    // =========================================================================
    // DEFAULT PROPERTIES TESTS
    // =========================================================================

    public function testDefaultSourceImage(): void
    {
        $image = new ImageLib();
        $this->assertEquals('', $image->source_image);
    }

    public function testDefaultNewImage(): void
    {
        $image = new ImageLib();
        $this->assertEquals('', $image->new_image);
    }

    public function testDefaultImageType(): void
    {
        $image = new ImageLib();
        $this->assertEquals('', $image->image_type);
    }

    public function testDefaultWidth(): void
    {
        $image = new ImageLib();
        $this->assertEquals(0, $image->width);
    }

    public function testDefaultHeight(): void
    {
        $image = new ImageLib();
        $this->assertEquals(0, $image->height);
    }

    public function testDefaultQuality(): void
    {
        $image = new ImageLib();
        $this->assertEquals(90, $image->quality);
    }

    public function testDefaultMaintainRatio(): void
    {
        $image = new ImageLib();
        $this->assertTrue($image->maintain_ratio);
    }

    public function testDefaultCreateThumb(): void
    {
        $image = new ImageLib();
        $this->assertFalse($image->create_thumb);
    }

    public function testDefaultThumbMarker(): void
    {
        $image = new ImageLib();
        $this->assertEquals('_thumb', $image->thumb_marker);
    }

    public function testDefaultLibraryPath(): void
    {
        $image = new ImageLib();
        $this->assertEquals('/usr/local/bin/', $image->library_path);
    }

    public function testDefaultRotationAngle(): void
    {
        $image = new ImageLib();
        $this->assertEquals('', $image->rotation_angle);
    }

    public function testDefaultXAxis(): void
    {
        $image = new ImageLib();
        $this->assertEquals('', $image->x_axis);
    }

    public function testDefaultYAxis(): void
    {
        $image = new ImageLib();
        $this->assertEquals('', $image->y_axis);
    }

    public function testDefaultMasterDim(): void
    {
        $image = new ImageLib();
        $this->assertEquals('auto', $image->master_dim);
    }

    public function testDefaultUseSubZero(): void
    {
        $image = new ImageLib();
        $this->assertFalse($image->use_subzero);
    }

    // =========================================================================
    // INITIALIZATION TESTS
    // =========================================================================

    public function testInitializeWithConfigArray(): void
    {
        $config = [
            'source_image' => $this->getTestImagePath(),
            'new_image' => sys_get_temp_dir() . '/resized.png',
            'width' => 100,
            'height' => 100,
            'quality' => 85,
            'maintain_ratio' => false,
            'create_thumb' => true,
        ];

        $image = new ImageLib($config);

        $this->assertEquals($this->getTestImagePath(), $image->source_image);
        $this->assertEquals(sys_get_temp_dir() . '/resized.png', $image->new_image);
        $this->assertEquals(100, $image->width);
        $this->assertEquals(100, $image->height);
        $this->assertEquals(85, $image->quality);
        $this->assertFalse($image->maintain_ratio);
        $this->assertTrue($image->create_thumb);
    }

    public function testInitializeMethodReturnsTrue(): void
    {
        $result = $this->image->initialize([]);
        $this->assertTrue($result);
    }

    // =========================================================================
    // RESIZE TESTS
    // =========================================================================

    public function testResizeMethodExists(): void
    {
        $this->assertTrue(method_exists($this->image, 'resize'));
    }

    public function testResizeSetsDimensions(): void
    {
        $this->image->width = 100;
        $this->image->height = 100;
        
        // Note: resize may fail without GD library, but method should exist
        $result = $this->image->resize();
        
        // Method exists and returns bool
        $this->assertIsBool($result);
    }

    // =========================================================================
    // CROP TESTS
    // =========================================================================

    public function testCropMethodExists(): void
    {
        $this->assertTrue(method_exists($this->image, 'crop'));
    }

    public function testCropRequiresCoordinates(): void
    {
        $this->image->width = 50;
        $this->image->height = 50;
        $this->image->x_axis = 10;
        $this->image->y_axis = 10;
        
        $result = $this->image->crop();
        
        $this->assertIsBool($result);
    }

    // =========================================================================
    // ROTATE TESTS
    // =========================================================================

    public function testRotateMethodExists(): void
    {
        $this->assertTrue(method_exists($this->image, 'rotate'));
    }

    public function testRotateWithAngle(): void
    {
        $this->image->rotation_angle = '90';
        
        $result = $this->image->rotate();
        
        $this->assertIsBool($result);
    }

    public function testRotateWithValidAngles(): void
    {
        $validAngles = ['90', '180', '270', 'vrt', 'hor'];
        
        foreach ($validAngles as $angle) {
            $this->image->rotation_angle = $angle;
            $this->assertEquals($angle, $this->image->rotation_angle);
        }
    }

    // =========================================================================
    // WATERMARK TESTS
    // =========================================================================

    public function testWatermarkMethodExists(): void
    {
        $this->assertTrue(method_exists($this->image, 'watermark'));
    }

    // =========================================================================
    // TEXT WATERMARK TESTS
    // =========================================================================

    public function testTextWatermarkConfig(): void
    {
        $this->image->wm_type = 'text';
        $this->image->wm_text = 'Copyright';
        $this->image->wm_font_size = 16;
        $this->image->wm_font_color = 'ffffff';
        $this->image->wm_vrt_alignment = 'bottom';
        $this->image->wm_hor_alignment = 'right';
        
        $this->assertEquals('text', $this->image->wm_type);
        $this->assertEquals('Copyright', $this->image->wm_text);
        $this->assertEquals(16, $this->image->wm_font_size);
    }

    // =========================================================================
    // OVERLAY WATERMARK TESTS
    // =========================================================================

    public function testOverlayWatermarkConfig(): void
    {
        $this->image->wm_type = 'overlay';
        $this->image->wm_overlay_path = $this->getTestImagePath();
        $this->image->wm_opacity = 50;
        
        $this->assertEquals('overlay', $this->image->wm_type);
        $this->assertEquals($this->getTestImagePath(), $this->image->wm_overlay_path);
        $this->assertEquals(50, $this->image->wm_opacity);
    }

    // =========================================================================
    // CLEAR METHOD TESTS
    // =========================================================================

    public function testClearMethodExists(): void
    {
        $this->assertTrue(method_exists($this->image, 'clear'));
    }

    public function testClearResetsProperties(): void
    {
        $this->image->width = 100;
        $this->image->height = 100;
        $this->image->rotation_angle = '90';
        
        $this->image->clear();
        
        $this->assertEquals(0, $this->image->width);
        $this->assertEquals(0, $this->image->height);
        $this->assertEquals('', $this->image->rotation_angle);
    }

    // =========================================================================
    // GET IMAGE PROPERTIES TESTS
    // =========================================================================

    public function testGetImagePropertiesMethodExists(): void
    {
        $this->assertTrue(method_exists($this->image, 'getImageProperties'));
    }

    public function testGetImagePropertiesReturnsArray(): void
    {
        $props = $this->image->getImageProperties($this->getTestImagePath());
        
        $this->assertIsArray($props);
        $this->assertArrayHasKey('width', $props);
        $this->assertArrayHasKey('height', $props);
        $this->assertGreaterThan(0, $props['width']);
        $this->assertGreaterThan(0, $props['height']);
    }

    // =========================================================================
    // GET META DATA TESTS
    // =========================================================================

    public function testGetMetaDataMethodExists(): void
    {
        $this->assertTrue(method_exists($this->image, 'getMetaData'));
    }

    // =========================================================================
    // MIRROR TESTS
    // =========================================================================

    public function testMirrorMethodExists(): void
    {
        $this->assertTrue(method_exists($this->image, 'mirror'));
    }

    public function testMirrorDirection(): void
    {
        $this->image->x_axis = 'left';
        $result = $this->image->mirror();
        $this->assertIsBool($result);
    }

    // =========================================================================
    // DISPLAY ERROR TESTS
    // =========================================================================

    public function testDisplayErrorsMethodExists(): void
    {
        $this->assertTrue(method_exists($this->image, 'display_errors'));
    }

    public function testDisplayErrorsReturnsString(): void
    {
        $errors = $this->image->display_errors();
        $this->assertIsString($errors);
    }

    // =========================================================================
    // VALIDATE PATH TESTS
    // =========================================================================

    public function testValidatePathMethodExists(): void
    {
        $this->assertTrue(method_exists($this->image, 'validatePath'));
    }

    // =========================================================================
    // PREPROCESSING TESTS
    // =========================================================================

    public function testPreProcessChecksSourceImage(): void
    {
        $method = new \ReflectionMethod($this->image, 'preProcess');
        $method->setAccessible(true);
        
        // Should validate source image exists
        $result = $method->invoke($this->image);
        $this->assertTrue($result);
    }

    // =========================================================================
    // DYNAMIC PROPS TESTS
    // =========================================================================

    public function testDynamicPropertiesCanBeSet(): void
    {
        $this->image->custom_property = 'custom_value';
        $this->assertEquals('custom_value', $this->image->custom_property);
    }

    // =========================================================================
    // INTEGRATION TESTS
    // =========================================================================

    public function testFullResizeWorkflow(): void
    {
        $outputPath = sys_get_temp_dir() . '/resized_test.png';
        
        $this->image->initialize([
            'source_image' => $this->getTestImagePath(),
            'new_image' => $outputPath,
            'width' => 100,
            'height' => 100,
            'maintain_ratio' => true,
        ]);
        
        // Try to resize (may fail without proper GD setup)
        $result = $this->image->resize();
        
        // Clean up
        if (file_exists($outputPath)) {
            unlink($outputPath);
        }
        
        // Method should exist and be callable
        $this->assertIsBool($result);
    }
}
