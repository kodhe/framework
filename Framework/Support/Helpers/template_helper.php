<?php

use Kodhe\Framework\View\ViewFactory;

if (!function_exists('view')) {
    /**
     * Universal view function dengan full feature support
     * 
     * @param string $view Nama view
     * @param array $data Data untuk view
     * @param bool $return Return sebagai string
     * @param mixed $layout Layout configuration
     * @param array $options Additional options
     * @return mixed
     */
    function view($view, $data = [], $return = false, $layout = null, $options = [])
    {
        static $template = null;
        
        // Initialize template jika belum
        if ($template === null) {
            $template = new ViewFactory($options);
            
            // Set global defaults
            $template->set([
                'app_name' => config_item('app_name') ?: 'My App',
                'base_url' => base_url(),
                'site_url' => site_url(),
                'current_url' => current_url(),
                'CI' => get_instance()
            ]);
        }
        
        // Reset sections jika needed
        if (!empty($options['reset_sections'])) {
            // Method ini perlu diimplementasikan di Template class
            if (method_exists($template, 'clear_sections')) {
                $template->clear_sections();
            }
        }
        
        // Apply layout jika diberikan
        if ($layout !== null) {
            apply_layout_config($template, $layout);
        }
        
        // Format view name
        $view = format_view_path($view);
        
        // Add view-specific options
        if (!empty($options['cache_key'])) {
            $data['_cache_key'] = $options['cache_key'];
        }
        
        if (!empty($options['cache_ttl'])) {
            $data['_cache_ttl'] = $options['cache_ttl'];
        }
        
        // Add theme mode info
        if (!empty($options['theme'])) {
            $data['_theme_mode'] = $options['theme'];
        }
        
        // Render view
        $result = $template->view($view, $data, $return);
        
        // Clear layout setelah render jika one-time
        if (!empty($options['layout_once'])) {
            $template->layout(null);
        }
        
        return $result;
    }
}

if (!function_exists('theme_view')) {
    /**
     * Render view dengan theme support
     * 
     * @param string $view Nama view
     * @param array $data Data untuk view
     * @param bool $return Return sebagai string
     * @param string $theme Nama theme (opsional)
     * @param mixed $layout Layout configuration
     * @param array $options Additional options
     * @return mixed
     */
    function theme_view($view, $data = [], $return = false, $theme = null, $layout = null, $options = [])
    {
        static $template = null;
        
        // Initialize template jika belum
        if ($template === null) {
            // Enable theme system
            $options['theme_enabled'] = true;
            $template = new ViewFactory($options);
        }
        
        // Set theme jika diberikan
        if ($theme !== null) {
            $template->setTheme($theme);
        }
        
        // Apply layout jika diberikan
        if ($layout !== null) {
            apply_layout_config($template, $layout);
        }
        
        // Format view name
        $view = format_view_path($view);
        
        // Add theme info to data
        $data['_theme_view'] = true;
        
        // Render view
        $result = $template->view($view, $data, $return);
        
        return $result;
    }
}

if (!function_exists('apply_layout_config')) {
    /**
     * Apply layout configuration to template
     */
    function apply_layout_config($template, $layout)
    {
        if (is_string($layout)) {
            $template->layout($layout);
        }
        elseif (is_array($layout)) {
            // Complex layout configuration
            if (isset($layout['name'])) {
                $template->layout($layout['name']);
            }
            
            if (isset($layout['data']) && is_array($layout['data'])) {
                foreach ($layout['data'] as $key => $value) {
                    $template->set($key, $value);
                }
            }
            
            if (isset($layout['sections']) && is_array($layout['sections'])) {
                foreach ($layout['sections'] as $name => $content) {
                    if (method_exists($template, 'section')) {
                        $template->section($name);
                        echo $content;
                        $template->endsection();
                    }
                }
            }
            
            if (isset($layout['extends'])) {
                if (method_exists($template, 'extends')) {
                    $template->extends($layout['extends']);
                }
            }
        }
    }
}

if (!function_exists('format_view_path')) {
    /**
     * Format view path for consistency
     */
    function format_view_path($view)
    {
        // Remove extension untuk konsistensi
        $view = preg_replace('/\.(blade\.php|php|lex\.php)$/i', '', $view);
        
        // Convert dot notation ke slash
        $view = str_replace('.', '/', $view);
        
        return $view;
    }
}

if (!function_exists('theme_asset')) {
    /**
     * Get theme asset URL
     * 
     * @param string $path Asset path relative to theme assets folder
     * @param string $theme Theme name (optional)
     * @return string Full URL to asset
     */
    function theme_asset($path, $theme = null)
    {
        static $template = null;
        
        if ($template === null) {
            $template = new ViewFactory(['theme_enabled' => true]);
        }
        
        // Set theme jika diberikan
        if ($theme !== null && $theme !== $template->getTheme()) {
            $template->setTheme($theme);
        }
        
        return $template->themeAsset($path);
    }
}

if (!function_exists('theme_partial')) {
    /**
     * Render theme partial
     * 
     * @param string $partial Partial view name
     * @param array $data Data for partial
     * @param string $theme Theme name (optional)
     * @return string Rendered partial
     */
    function theme_partial($partial, $data = [], $theme = null)
    {
        return theme_view('partials/' . $partial, $data, true, $theme);
    }
}

if (!function_exists('theme_widget')) {
    /**
     * Render theme widget
     * 
     * @param string $widget Widget name
     * @param array $params Widget parameters
     * @param string $theme Theme name (optional)
     * @return string Rendered widget
     */
    function theme_widget($widget, $params = [], $theme = null)
    {
        return theme_view('widgets/' . $widget, $params, true, $theme);
    }
}

if (!function_exists('theme_info')) {
    /**
     * Get theme information
     * 
     * @param string $key Specific key to retrieve (optional)
     * @param string $theme Theme name (optional)
     * @return mixed Theme information
     */
    function theme_info($key = null, $theme = null)
    {
        static $template = null;
        
        if ($template === null) {
            $template = new ViewFactory(['theme_enabled' => true]);
        }
        
        // Set theme jika diberikan
        if ($theme !== null && $theme !== $template->getTheme()) {
            $template->setTheme($theme);
        }
        
        return $template->getThemeInfo($key);
    }
}

if (!function_exists('get_active_theme')) {
    /**
     * Get active theme name
     * 
     * @return string Active theme name
     */
    function get_active_theme()
    {
        static $template = null;
        
        if ($template === null) {
            $template = new ViewFactory(['theme_enabled' => true]);
        }
        
        return $template->getTheme();
    }
}

if (!function_exists('get_available_themes')) {
    /**
     * Get all available themes
     * 
     * @return array List of available themes
     */
    function get_available_themes()
    {
        static $template = null;
        
        if ($template === null) {
            $template = new ViewFactory(['theme_enabled' => true]);
        }
        
        return $template->getAvailableThemes();
    }
}

if (!function_exists('set_theme_preview')) {
    /**
     * Set theme preview for current session
     * 
     * @param string $theme Theme name to preview
     * @return void
     */
    function set_theme_preview($theme)
    {
        $CI =& get_instance();
        $CI->load->library('session');
        $CI->session->set_userdata('theme_preview', $theme);
    }
}

if (!function_exists('clear_theme_preview')) {
    /**
     * Clear theme preview
     * 
     * @return void
     */
    function clear_theme_preview()
    {
        $CI =& get_instance();
        $CI->load->library('session');
        $CI->session->unset_userdata('theme_preview');
    }
}

if (!function_exists('is_mobile_theme')) {
    /**
     * Check if mobile theme is active
     * 
     * @return bool True if mobile theme is active
     */
    function is_mobile_theme()
    {
        $active_theme = get_active_theme();
        $CI =& get_instance();
        $CI->config->load('template', true);
        $config = $CI->config->item('template');
        
        return isset($config['theme_mobile']) && $active_theme === $config['theme_mobile'];
    }
}

if (!function_exists('is_admin_theme')) {
    /**
     * Check if admin theme is active
     * 
     * @return bool True if admin theme is active
     */
    function is_admin_theme()
    {
        $active_theme = get_active_theme();
        $CI =& get_instance();
        $CI->config->load('template', true);
        $config = $CI->config->item('template');
        
        return isset($config['theme_admin']) && $active_theme === $config['theme_admin'];
    }
}

if (!function_exists('theme_region')) {
    /**
     * Render theme region/widget area
     * 
     * @param string $region Region name
     * @param array $data Additional data
     * @param string $theme Theme name (optional)
     * @return string Rendered region content
     */
    function theme_region($region, $data = [], $theme = null)
    {
        // Cek jika ada widget untuk region ini
        $CI =& get_instance();
        
        // Load widget library jika ada
        if (class_exists('Widget_manager')) {
            $CI->load->library('widget_manager');
            if (method_exists($CI->widget_manager, 'render_region')) {
                return $CI->widget_manager->render_region($region, $data);
            }
        }
        
        // Fallback ke partial dengan nama region
        return theme_partial('regions/' . $region, $data, $theme);
    }
}

if (!function_exists('template_exists')) {
    /**
     * Check if template/view exists
     * 
     * @param string $view View name
     * @return bool True if view exists
     */
    function template_exists($view)
    {
        static $template = null;
        
        if ($template === null) {
            $template = new ViewFactory();
        }
        
        return $template->exists($view);
    }
}


if (!function_exists('theme_variant')) {
    /**
     * Get current theme variant
     */
    function theme_variant()
    {
        $CI =& get_instance();
        if (isset($CI->template) && $CI->template->theme()) {
            return $CI->template->theme()->getVariant();
        }
        return null;
    }
}

if (!function_exists('is_mobile_variant')) {
    /**
     * Check if mobile variant is active
     */
    function is_mobile_variant()
    {
        return theme_variant() === 'mobile';
    }
}

if (!function_exists('theme_asset')) {
    /**
     * Get theme asset URL
     */
    function theme_asset($path)
    {
        $CI =& get_instance();
        if (isset($CI->template) && $CI->template->assets()) {
            return $CI->template->assets()->url($path);
        }
        return base_url('assets/' . ltrim($path, '/'));
    }
}

if (!function_exists('theme_view')) {
    /**
     * Render view dengan variant support
     */
    function theme_view($view, $data = [], $return = false)
    {
        $CI =& get_instance();
        if (isset($CI->template)) {
            return $CI->template->view($view, $data, $return);
        }
        return $CI->load->view($view, $data, $return);
    }
}

if (!function_exists('theme_switch_variant')) {
    /**
     * Switch theme variant
     */
    function theme_switch_variant($variant)
    {
        $CI =& get_instance();
        if (isset($CI->template) && $CI->template->theme()) {
            $CI->template->theme()->setVariant($variant);
            
            // Save to session
            $CI->session->set_userdata('theme_variant', $variant);
            return true;
        }
        return false;
    }
}

// Tambahkan di akhir file template_helper.php sebelum penutup

if (!function_exists('enable_theme')) {
    /**
     * Enable or disable theme system
     * 
     * @param bool $enabled True to enable, false to disable
     * @param string|null $themeName Specific theme to set (optional)
     * @return ViewFactory Template instance for chaining
     */
    function enable_theme($enabled = true, $themeName = null)
    {
        $CI =& get_instance();
        
        // Jika template sudah di-load di CI instance
        if (isset($CI->template)) {
            $template = $CI->template;
        } else {
            // Create new instance
            $template = new ViewFactory();
            $CI->template = $template;
        }
        
        // Enable/disable theme
        $template->enableTheme($enabled);
        
        // Set specific theme if provided and enabled
        if ($enabled && $themeName !== null) {
            $template->setTheme($themeName);
        }
        
        return $template;
    }
}

if (!function_exists('disable_theme')) {
    /**
     * Disable theme system (alias for enable_theme(false))
     * 
     * @return ViewFactory Template instance for chaining
     */
    function disable_theme()
    {
        return enable_theme(false);
    }
}
