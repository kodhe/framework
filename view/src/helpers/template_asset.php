<?php
/**
 * CodeIgniter 3 Asset Helper Functions
 *
 * Provides backward compatible helper functions for asset management
 * while supporting the new modular architecture.
 *
 * @package Kodhe\Framework\View
 * @author  Kodhe Framework Team
 * @since   1.0.0
 */

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

use Kodhe\Framework\View\Asset\AssetManager;
use Kodhe\Framework\View\Asset\Asset;
use Kodhe\Framework\View\Asset\AssetCollection;

// ============================================================================
// ASSET REGISTRATION FUNCTIONS
// ============================================================================

if (!function_exists('register_asset')) {
    /**
     * Register an asset (CSS or JS)
     *
     * @param string $type     Asset type ('css' or 'js')
     * @param string $path     Asset path or URL
     * @param array  $options  Additional options (version, media, async, defer, etc.)
     * @return void
     */
    function register_asset($type, $path, $options = [])
    {
        $assetManager = AssetManager::getInstance();
        $assetManager->register($type, $path, $options);
    }
}

if (!function_exists('register_css')) {
    /**
     * Register a CSS stylesheet
     *
     * @param string $path    CSS file path or URL
     * @param array  $options Options (media, version, etc.)
     * @return void
     */
    function register_css($path, $options = [])
    {
        $assetManager = AssetManager::getInstance();
        $assetManager->registerCss($path, $options);
    }
}

if (!function_exists('register_js')) {
    /**
     * Register a JavaScript file
     *
     * @param string $path    JS file path or URL
     * @param array  $options Options (async, defer, version, position)
     * @return void
     */
    function register_js($path, $options = [])
    {
        $assetManager = AssetManager::getInstance();
        $assetManager->registerJs($path, $options);
    }
}

if (!function_exists('register_inline_css')) {
    /**
     * Register inline CSS
     *
     * @param string $css     CSS content
     * @param string $id      Unique identifier for the inline CSS
     * @return void
     */
    function register_inline_css($css, $id = null)
    {
        $assetManager = AssetManager::getInstance();
        $assetManager->registerInlineCss($css, $id);
    }
}

if (!function_exists('register_inline_js')) {
    /**
     * Register inline JavaScript
     *
     * @param string $js      JS content
     * @param string $id      Unique identifier for the inline JS
     * @param string $position Position ('head' or 'footer', default: 'footer')
     * @return void
     */
    function register_inline_js($js, $id = null, $position = 'footer')
    {
        $assetManager = AssetManager::getInstance();
        $assetManager->registerInlineJs($js, $id, $position);
    }
}

// ============================================================================
// ASSET REMOVAL FUNCTIONS
// ============================================================================

if (!function_exists('remove_asset')) {
    /**
     * Remove a registered asset
     *
     * @param string $type Asset type ('css' or 'js')
     * @param string $path Asset path to remove
     * @return bool True if removed, false if not found
     */
    function remove_asset($type, $path)
    {
        $assetManager = AssetManager::getInstance();
        return $assetManager->remove($type, $path);
    }
}

if (!function_exists('remove_css')) {
    /**
     * Remove a registered CSS file
     *
     * @param string $path CSS path to remove
     * @return bool
     */
    function remove_css($path)
    {
        return remove_asset('css', $path);
    }
}

if (!function_exists('remove_js')) {
    /**
     * Remove a registered JS file
     *
     * @param string $path JS path to remove
     * @return bool
     */
    function remove_js($path)
    {
        return remove_asset('js', $path);
    }
}

// ============================================================================
// ASSET RENDERING FUNCTIONS
// ============================================================================

if (!function_exists('render_assets')) {
    /**
     * Render all registered assets of a specific type
     *
     * @param string $type Asset type ('css' or 'js')
     * @param bool   $return Whether to return instead of echo
     * @return string|void
     */
    function render_assets($type, $return = false)
    {
        $assetManager = AssetManager::getInstance();
        $output = $assetManager->render($type);
        
        if ($return) {
            return $output;
        }
        
        echo $output;
    }
}

if (!function_exists('render_css')) {
    /**
     * Render all registered CSS files
     *
     * @param bool $return Whether to return instead of echo
     * @return string|void
     */
    function render_css($return = false)
    {
        return render_assets('css', $return);
    }
}

if (!function_exists('render_js')) {
    /**
     * Render all registered JS files
     *
     * @param bool   $return   Whether to return instead of echo
     * @param string $position Position ('head' or 'footer')
     * @return string|void
     */
    function render_js($return = false, $position = null)
    {
        $assetManager = AssetManager::getInstance();
        $output = $assetManager->renderJs($position);
        
        if ($return) {
            return $output;
        }
        
        echo $output;
    }
}

if (!function_exists('render_head_assets')) {
    /**
     * Render all assets that should appear in the head section
     *
     * @param bool $return Whether to return instead of echo
     * @return string|void
     */
    function render_head_assets($return = false)
    {
        $assetManager = AssetManager::getInstance();
        $output = $assetManager->renderHead();
        
        if ($return) {
            return $output;
        }
        
        echo $output;
    }
}

if (!function_exists('render_footer_assets')) {
    /**
     * Render all assets that should appear in the footer
     *
     * @param bool $return Whether to return instead of echo
     * @return string|void
     */
    function render_footer_assets($return = false)
    {
        $assetManager = AssetManager::getInstance();
        $output = $assetManager->renderFooter();
        
        if ($return) {
            return $output;
        }
        
        echo $output;
    }
}

// ============================================================================
// ASSET URL/PATH FUNCTIONS
// ============================================================================

if (!function_exists('asset_url')) {
    /**
     * Get the full URL for an asset
     *
     * @param string $path Relative path to asset
     * @param bool   $addVersion Whether to add version query parameter
     * @return string
     */
    function asset_url($path = '', $addVersion = true)
    {
        $assetManager = AssetManager::getInstance();
        return $assetManager->getAssetUrl($path, $addVersion);
    }
}

if (!function_exists('asset_path')) {
    /**
     * Get the server path for an asset
     *
     * @param string $path Relative path to asset
     * @return string
     */
    function asset_path($path = '')
    {
        $assetManager = AssetManager::getInstance();
        return $assetManager->getAssetPath($path);
    }
}

if (!function_exists('base_asset')) {
    /**
     * Get base asset URL (shortcut for asset_url)
     *
     * @param string $path Path to asset
     * @return string
     */
    function base_asset($path = '')
    {
        return asset_url($path);
    }
}

// ============================================================================
// ASSET COLLECTION FUNCTIONS
// ============================================================================

if (!function_exists('add_to_collection')) {
    /**
     * Add an asset to a named collection
     *
     * @param string $collection Collection name
     * @param string $type       Asset type ('css' or 'js')
     * @param string $path       Asset path
     * @param array  $options    Options
     * @return void
     */
    function add_to_collection($collection, $type, $path, $options = [])
    {
        $assetManager = AssetManager::getInstance();
        $assetManager->addToCollection($collection, $type, $path, $options);
    }
}

if (!function_exists('render_collection')) {
    /**
     * Render a named asset collection
     *
     * @param string $collection Collection name
     * @param bool   $return     Whether to return instead of echo
     * @return string|void
     */
    function render_collection($collection, $return = false)
    {
        $assetManager = AssetManager::getInstance();
        $output = $assetManager->renderCollection($collection);
        
        if ($return) {
            return $output;
        }
        
        echo $output;
    }
}

if (!function_exists('clear_collection')) {
    /**
     * Clear a named asset collection
     *
     * @param string $collection Collection name
     * @return void
     */
    function clear_collection($collection)
    {
        $assetManager = AssetManager::getInstance();
        $assetManager->clearCollection($collection);
    }
}

// ============================================================================
// CACHE BUSTING FUNCTIONS
// ============================================================================

if (!function_exists('asset_version')) {
    /**
     * Get version string for cache busting
     *
     * @param string $path Asset path
     * @return string Version string
     */
    function asset_version($path)
    {
        $assetManager = AssetManager::getInstance();
        return $assetManager->getVersion($path);
    }
}

if (!function_exists('set_asset_version')) {
    /**
     * Set a specific version for an asset
     *
     * @param string $path    Asset path
     * @param string $version Version string
     * @return void
     */
    function set_asset_version($path, $version)
    {
        $assetManager = AssetManager::getInstance();
        $assetManager->setVersion($path, $version);
    }
}

if (!function_exists('enable_cache_busting')) {
    /**
     * Enable automatic cache busting based on file modification time
     *
     * @param bool $enabled Whether to enable cache busting
     * @return void
     */
    function enable_cache_busting($enabled = true)
    {
        $assetManager = AssetManager::getInstance();
        $assetManager->enableCacheBusting($enabled);
    }
}

// ============================================================================
// CDN FUNCTIONS
// ============================================================================

if (!function_exists('set_cdn_url')) {
    /**
     * Set CDN base URL for assets
     *
     * @param string $url CDN base URL
     * @return void
     */
    function set_cdn_url($url)
    {
        $assetManager = AssetManager::getInstance();
        $assetManager->setCdnUrl($url);
    }
}

if (!function_exists('get_cdn_url')) {
    /**
     * Get the current CDN base URL
     *
     * @return string
     */
    function get_cdn_url()
    {
        $assetManager = AssetManager::getInstance();
        return $assetManager->getCdnUrl();
    }
}

if (!function_exists('use_cdn')) {
    /**
     * Check if CDN is being used
     *
     * @return bool
     */
    function use_cdn()
    {
        $assetManager = AssetManager::getInstance();
        return $assetManager->isUsingCdn();
    }
}

// ============================================================================
// HELPER INITIALIZATION
// ============================================================================

/**
 * Initialize asset helpers
 * Called automatically when helper is loaded
 */
if (!function_exists('_init_asset_helpers')) {
    function _init_asset_helpers()
    {
        // Initialize asset manager
        $assetManager = AssetManager::getInstance();
        
        // Load default configuration if available
        $CI =& get_instance();
        if (isset($CI->config)) {
            $cdnUrl = $CI->config->item('asset_cdn_url');
            if ($cdnUrl) {
                $assetManager->setCdnUrl($cdnUrl);
            }
            
            $assetPath = $CI->config->item('asset_path');
            if ($assetPath) {
                $assetManager->setAssetPath($assetPath);
            }
        }
    }
    
    // Auto-initialize if CI is available
    if (class_exists('CI_Controller')) {
        _init_asset_helpers();
    }
}
