<?php
/**
 * CodeIgniter 3 View Helper Functions
 *
 * Provides backward compatible helper functions for view rendering
 * while supporting the new modular architecture.
 *
 * @package Kodhe\Framework\View
 * @author  Kodhe Framework Team
 * @since   1.0.0
 */

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

use Kodhe\Framework\View\View\View;
use Kodhe\Framework\View\View\ViewFactory;
use Kodhe\Framework\View\View\ViewLoader;
use Kodhe\Framework\View\View\ViewContext;
use Kodhe\Framework\View\Theme\ThemeManager;
use Kodhe\Framework\View\Variant\VariantManager;
use Kodhe\Framework\View\Asset\AssetManager;
use Kodhe\Framework\View\Support\ViewConfig;

// ============================================================================
// CORE VIEW FUNCTIONS
// ============================================================================

if (!function_exists('view')) {
    /**
     * Render a view and return/output the content.
     * Simple alias for template() function.
     *
     * @param string $view   View name to render
     * @param array  $data   Data to pass to the view
     * @param bool   $return Whether to return the rendered output instead of displaying
     * @return string|void
     */
    function view($view, $data = [], $return = false)
    {
        return template($view, $data, $return);
    }
}

// ============================================================================
// TEMPLATE RENDERING FUNCTIONS
// ============================================================================

if (!function_exists('template')) {
    /**
     * Render a template with optional data and theme
     *
     * @param string $view      View name to render
     * @param array  $data      Data to pass to the view
     * @param bool   $return    Whether to return the rendered output instead of displaying
     * @param string|null $theme Optional theme name (uses default if null)
     * @return string|void
     */
    function template($view, $data = [], $return = false, $theme = null)
    {
        // Get CI super object for backward compatibility
        $CI =& get_instance();
        
        // Create view context
        $context = new ViewContext($data);
        
        // Set theme if provided
        if ($theme !== null) {
            $context->setTheme($theme);
        }
        
        // Get or create view instance
        $viewFactory = ViewFactory::getInstance();
        $viewInstance = $viewFactory->create($view, $context);
        
        // Render the view
        $output = $viewInstance->render();
        
        if ($return) {
            return $output;
        }
        
        echo $output;
    }
}

if (!function_exists('template_partial')) {
    /**
     * Render a partial template (component/fragment)
     *
     * @param string $view   Partial view name
     * @param array  $data   Data to pass to the partial
     * @param bool   $return Whether to return the output
     * @return string|void
     */
    function template_partial($view, $data = [], $return = false)
    {
        return template($view, $data, $return);
    }
}

if (!function_exists('template_render')) {
    /**
     * Render a view using specific engine
     *
     * @param string $view    View name
     * @param array  $data    Data array
     * @param string $engine  Engine type ('php', 'blade', 'twig')
     * @param bool   $return  Return or echo
     * @return string|void
     */
    function template_render($view, $data = [], $engine = 'php', $return = false)
    {
        $CI =& get_instance();
        
        $context = new ViewContext($data);
        $context->setEngine($engine);
        
        $viewFactory = ViewFactory::getInstance();
        $viewInstance = $viewFactory->create($view, $context);
        
        $output = $viewInstance->render();
        
        if ($return) {
            return $output;
        }
        
        echo $output;
    }
}

// ============================================================================
// THEME MANAGEMENT FUNCTIONS
// ============================================================================

if (!function_exists('set_theme')) {
    /**
     * Set the active theme
     *
     * @param string $theme Theme name
     * @return void
     */
    function set_theme($theme)
    {
        $themeManager = ThemeManager::getInstance();
        $themeManager->setActiveTheme($theme);
    }
}

if (!function_exists('get_theme')) {
    /**
     * Get the current active theme
     *
     * @return string
     */
    function get_theme()
    {
        $themeManager = ThemeManager::getInstance();
        return $themeManager->getActiveTheme();
    }
}

if (!function_exists('theme_url')) {
    /**
     * Get URL for a theme asset
     *
     * @param string $path Relative path to theme asset
     * @return string
     */
    function theme_url($path = '')
    {
        $themeManager = ThemeManager::getInstance();
        return $themeManager->getThemeUrl($path);
    }
}

if (!function_exists('theme_path')) {
    /**
     * Get server path for a theme asset
     *
     * @param string $path Relative path to theme asset
     * @return string
     */
    function theme_path($path = '')
    {
        $themeManager = ThemeManager::getInstance();
        return $themeManager->getThemePath($path);
    }
}

if (!function_exists('has_theme')) {
    /**
     * Check if a theme exists
     *
     * @param string $theme Theme name
     * @return bool
     */
    function has_theme($theme)
    {
        $themeManager = ThemeManager::getInstance();
        return $themeManager->themeExists($theme);
    }
}

// ============================================================================
// VARIANT DETECTION FUNCTIONS
// ============================================================================

if (!function_exists('is_mobile')) {
    /**
     * Check if the current request is from a mobile device
     *
     * @return bool
     */
    function is_mobile()
    {
        $variantManager = VariantManager::getInstance();
        return $variantManager->isMobile();
    }
}

if (!function_exists('is_tablet')) {
    /**
     * Check if the current request is from a tablet device
     *
     * @return bool
     */
    function is_tablet()
    {
        $variantManager = VariantManager::getInstance();
        return $variantManager->isTablet();
    }
}

if (!function_exists('is_desktop')) {
    /**
     * Check if the current request is from a desktop device
     *
     * @return bool
     */
    function is_desktop()
    {
        $variantManager = VariantManager::getInstance();
        return $variantManager->isDesktop();
    }
}

if (!function_exists('get_variant')) {
    /**
     * Get the current device variant
     *
     * @return string ('mobile', 'tablet', or 'desktop')
     */
    function get_variant()
    {
        $variantManager = VariantManager::getInstance();
        return $variantManager->getCurrentVariant();
    }
}

// ============================================================================
// VIEW CONFIGURATION FUNCTIONS
// ============================================================================

if (!function_exists('set_view_data')) {
    /**
     * Set global view data that will be available to all views
     *
     * @param string|array $key   Key or array of key-value pairs
     * @param mixed        $value Value (if key is string)
     * @return void
     */
    function set_view_data($key, $value = null)
    {
        $config = ViewConfig::getInstance();
        $config->setData($key, $value);
    }
}

if (!function_exists('get_view_data')) {
    /**
     * Get global view data
     *
     * @param string|null $key Specific key or null for all data
     * @return mixed
     */
    function get_view_data($key = null)
    {
        $config = ViewConfig::getInstance();
        return $config->getData($key);
    }
}

if (!function_exists('clear_view_data')) {
    /**
     * Clear all global view data
     *
     * @return void
     */
    function clear_view_data()
    {
        $config = ViewConfig::getInstance();
        $config->clearData();
    }
}

// ============================================================================
// BACKWARD COMPATIBILITY WITH CODEIGNITER 3
// ============================================================================

if (!function_exists('load_view')) {
    /**
     * CodeIgniter 3 compatible load view function
     * Alias for $this->load->view()
     *
     * @param string $view   View name
     * @param array  $vars   Variables
     * @param bool   $return Return or echo
     * @return string|void
     */
    function load_view($view, $vars = [], $return = false)
    {
        $CI =& get_instance();
        return $CI->load->view($view, $vars, $return);
    }
}

if (!function_exists('view_exists')) {
    /**
     * Check if a view file exists
     *
     * @param string $view View name
     * @return bool
     */
    function view_exists($view)
    {
        $CI =& get_instance();
        $loader = new ViewLoader();
        return $loader->viewExists($view);
    }
}

// ============================================================================
// LAYOUT FUNCTIONS
// ============================================================================

if (!function_exists('layout')) {
    /**
     * Render a layout with content
     *
     * @param string $layout  Layout name
     * @param string $content Content to inject into layout
     * @param array  $data    Additional data
     * @param bool   $return  Return or echo
     * @return string|void
     */
    function layout($layout, $content = '', $data = [], $return = false)
    {
        $data['content'] = $content;
        return template($layout, $data, $return);
    }
}

if (!function_exists('content')) {
    /**
     * Output content placeholder in layouts
     * This is used within layout files to output the main content
     *
     * @return void
     */
    function content()
    {
        static $content = '';
        
        // If called with arguments, set the content
        if (func_num_args() > 0) {
            $content = func_get_arg(0);
            return;
        }
        
        // Otherwise, output the content
        echo $content;
    }
}

// ============================================================================
// HELPER INITIALIZATION
// ============================================================================

/**
 * Initialize view helpers with CodeIgniter instance
 * Called automatically when helper is loaded
 */
if (!function_exists('_init_view_helpers')) {
    function _init_view_helpers()
    {
        $CI =& get_instance();
        
        // Ensure view config is initialized
        ViewConfig::getInstance();
        
        // Initialize theme manager with CI config
        $themeManager = ThemeManager::getInstance();
        
        // Initialize variant manager
        $variantManager = VariantManager::getInstance();
        
        // Initialize asset manager
        $assetManager = AssetManager::getInstance();
    }
    
    // Auto-initialize if CI is available
    if (class_exists('CI_Controller')) {
        _init_view_helpers();
    }
}
