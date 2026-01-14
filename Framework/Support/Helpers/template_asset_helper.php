<?php

if (!function_exists('theme_asset')) {
    function theme_asset($path, $theme = null)
    {
        $CI =& get_instance();
        
        // Cek apakah assets manager tersedia
        if (isset($CI->assets) && $CI->assets instanceof Kodhe\Framework\Assets\Assets) {
            return $CI->assets->theme_asset($path, $theme);
        }
        
        // Fallback jika assets manager tidak ada
        if (!$theme && isset($CI->template) && method_exists($CI->template, 'theme')) {
            $theme = $CI->template->theme()->getTheme();
        }
        
        $path = ltrim($path, '/');
        
        if ($theme) {
            $theme_path = base_url('themes/' . $theme . '/assets/' . $path);
            
            // Check if file exists
            $full_path = FCPATH . 'themes/' . $theme . '/assets/' . $path;
            if (file_exists($full_path)) {
                return $theme_path;
            }
        }
        
        return base_url('assets/' . $path);
    }
}

if (!function_exists('theme_css')) {
    function theme_css($file, $theme = null)
    {
        return theme_asset('css/' . ltrim($file, '/'), $theme);
    }
}

if (!function_exists('theme_js')) {
    function theme_js($file, $theme = null)
    {
        return theme_asset('js/' . ltrim($file, '/'), $theme);
    }
}

if (!function_exists('theme_img')) {
    function theme_img($file, $theme = null)
    {
        return theme_asset('img/' . ltrim($file, '/'), $theme);
    }
}

if (!function_exists('add_css')) {
    function add_css($file, $group = 'theme', $attributes = [], $priority = 10)
    {
        $CI =& get_instance();
        
        if (isset($CI->assets) && $CI->assets instanceof Kodhe\Framework\Assets\Assets) {
            return $CI->assets->add_css($file, $group, $attributes, $priority);
        }
        
        // Fallback: langsung output link tag
        $url = theme_asset('css/' . ltrim($file, '/'));
        $attrs = '';
        foreach ($attributes as $key => $value) {
            if (is_numeric($key)) {
                $attrs .= $value . ' ';
            } else {
                $attrs .= $key . '="' . htmlspecialchars($value) . '" ';
            }
        }
        
        echo '<link rel="stylesheet" href="' . $url . '" ' . trim($attrs) . '>' . PHP_EOL;
        return null;
    }
}

if (!function_exists('add_js')) {
    function add_js($file, $group = 'theme', $attributes = [], $position = 'footer', $priority = 10)
    {
        $CI =& get_instance();
        
        if (isset($CI->assets) && $CI->assets instanceof Kodhe\Framework\Assets\Assets) {
            return $CI->assets->add_js($file, $group, $attributes, $position, $priority);
        }
        
        // Fallback: langsung output script tag
        $url = theme_asset('js/' . ltrim($file, '/'));
        $attrs = '';
        foreach ($attributes as $key => $value) {
            if (is_numeric($key)) {
                $attrs .= $value . ' ';
            } else {
                $attrs .= $key . '="' . htmlspecialchars($value) . '" ';
            }
        }
        
        if ($position === 'header') {
            echo '<script src="' . $url . '" ' . trim($attrs) . '></script>' . PHP_EOL;
        } else {
            // Simpan untuk footer
            if (!isset($CI->_footer_js)) {
                $CI->_footer_js = [];
            }
            $CI->_footer_js[] = '<script src="' . $url . '" ' . trim($attrs) . '></script>' . PHP_EOL;
        }
        return null;
    }
}

if (!function_exists('add_inline_css')) {
    function add_inline_css($css, $priority = 10)
    {
        $CI =& get_instance();
        
        if (isset($CI->assets) && $CI->assets instanceof Kodhe\Framework\Assets\Assets) {
            return $CI->assets->add_inline_css($css, $priority);
        }
        
        // Fallback: langsung output style tag
        echo '<style>' . $css . '</style>' . PHP_EOL;
        return null;
    }
}

if (!function_exists('add_inline_js')) {
    function add_inline_js($js, $position = 'footer', $priority = 10)
    {
        $CI =& get_instance();
        
        if (isset($CI->assets) && $CI->assets instanceof Kodhe\Framework\Assets\Assets) {
            return $CI->assets->add_inline_js($js, $position, $priority);
        }
        
        // Fallback: langsung output script tag
        if ($position === 'header') {
            echo '<script>' . $js . '</script>' . PHP_EOL;
        } else {
            // Simpan untuk footer
            if (!isset($CI->_footer_inline_js)) {
                $CI->_footer_inline_js = [];
            }
            $CI->_footer_inline_js[] = '<script>' . $js . '</script>' . PHP_EOL;
        }
        return null;
    }
}

if (!function_exists('render_css')) {
    function render_css($group = 'theme')
    {
        $CI =& get_instance();
        
        if (isset($CI->assets) && $CI->assets instanceof Kodhe\Framework\Assets\Assets) {
            return $CI->assets->render_css($group);
        }
        
        return '';
    }
}

if (!function_exists('render_js')) {
    function render_js($position = 'footer', $group = 'theme')
    {
        $CI =& get_instance();
        
        if (isset($CI->assets) && $CI->assets instanceof Kodhe\Framework\Assets\Assets) {
            return $CI->assets->render_js($position, $group);
        }
        
        // Fallback untuk footer JS
        if ($position === 'footer') {
            $output = '';
            if (isset($CI->_footer_js) && is_array($CI->_footer_js)) {
                $output .= implode('', $CI->_footer_js);
            }
            if (isset($CI->_footer_inline_js) && is_array($CI->_footer_inline_js)) {
                $output .= implode('', $CI->_footer_inline_js);
            }
            return $output;
        }
        
        return '';
    }
}

if (!function_exists('render_meta')) {
    function render_meta()
    {
        $CI =& get_instance();
        
        if (isset($CI->assets) && $CI->assets instanceof Kodhe\Framework\Assets\Assets) {
            return $CI->assets->render_meta();
        }
        
        return '';
    }
}

if (!function_exists('asset_exists')) {
    function asset_exists($path, $theme = null)
    {
        $CI =& get_instance();
        
        if (isset($CI->assets) && $CI->assets instanceof Kodhe\Framework\Assets\Assets) {
            return $CI->assets->exists($path, $theme);
        }
        
        // Fallback check
        if (!$theme && isset($CI->template) && method_exists($CI->template, 'theme')) {
            $theme = $CI->template->theme()->getTheme();
        }
        
        if ($theme) {
            $full_path = FCPATH . 'themes/' . $theme . '/assets/' . ltrim($path, '/');
            return file_exists($full_path);
        }
        
        return false;
    }
}

if (!function_exists('clear_assets')) {
    function clear_assets($type = null)
    {
        $CI =& get_instance();
        
        if (isset($CI->assets) && $CI->assets instanceof Kodhe\Framework\Assets\Assets) {
            return $CI->assets->clear_assets($type);
        }
        
        return null;
    }
}