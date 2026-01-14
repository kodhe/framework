<?php
/**
 * Module Helper Functions
 */

if (!function_exists('current_module')) {
    /**
     * Get current module name
     */
    function current_module(): string
    {
        // Priority 1: Dari CI instance
        if (function_exists('get_instance')) {
            $CI = get_instance();
            if (property_exists($CI, 'module') && !empty($CI->module)) {
                return $CI->module;
            }
        }
        
        // Priority 2: Dari Modules class
        if (class_exists('\Kodhe\Framework\Support\Modules')) {
            $module = \Kodhe\Framework\Support\Modules::getCurrentModule();
            if (!empty($module)) {
                return $module;
            }
        }
        
        // Priority 3: Dari facade system
        if (class_exists('Kodhe\Framework\Support\Facades\Facade')) {
            $facade = \Kodhe\Framework\Support\Facades\Facade::getInstance();
            
            // Coba dari container
            if ($facade->has('di')) {
                $container = $facade->get('di');
                if ($container->has('current.module')) {
                    return $container->make('current.module');
                }
            }
            
            // Coba dari facade key langsung
            if ($facade->has('current.module')) {
                return $facade->get('current.module');
            }
            
            // Coba dari routing manager
            if ($facade->has('routing.manager')) {
                $manager = $facade->get('routing.manager');
                if (method_exists($manager, 'getModule')) {
                    return $manager->getModule();
                }
            }
            
            // Coba dari controller executor
            if ($facade->has('controller.executor')) {
                $executor = $facade->get('controller.executor');
                if (method_exists($executor, 'getCurrentModule')) {
                    return $executor->getCurrentModule();
                }
            }
        }
        
        // Priority 4: Dari URI
        $uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        $segments = explode('/', trim(parse_url($uri, PHP_URL_PATH), '/'));
        if (!empty($segments[0]) && \Kodhe\Framework\Support\Modules::moduleExists($segments[0])) {
            return $segments[0];
        }
        
        return '';
    }
}

if (!function_exists('is_module')) {
    /**
     * Check if current module matches
     */
    function is_module(string $module): bool
    {
        return current_module() === $module;
    }
}

if (!function_exists('module_path')) {
    /**
     * Get module path
     */
    function module_path(string $module = '', string $subpath = ''): string
    {
        if (empty($module)) {
            $module = current_module();
        }
        
        if (empty($module)) {
            return '';
        }
        
        $path = \Kodhe\Framework\Support\Modules::path($module);
        if (!$path) {
            return '';
        }
        
        if (!empty($subpath)) {
            $fullPath = rtrim($path, '/') . '/' . ltrim($subpath, '/');
            return $fullPath;
        }
        
        return $path;
    }
}

if (!function_exists('module_controller_exists')) {
    /**
     * Check if controller exists in module
     */
    function module_controller_exists(string $controller, string $module = ''): bool
    {
        if (empty($module)) {
            $module = current_module();
        }
        
        if (empty($module) || empty($controller)) {
            return false;
        }
        
        return \Kodhe\Framework\Support\Modules::controller_exists($controller, $module);
    }
}

if (!function_exists('module_view')) {
    /**
     * Load view from module
     */
    function module_view(string $view, array $data = [], bool $return = false)
    {
        $module = current_module();
        if (empty($module)) {
            show_error('Cannot load module view: no current module');
        }
        
        $viewPath = "modules/{$module}/views/{$view}";
        
        if (function_exists('get_instance')) {
            $CI = get_instance();
            if (method_exists($CI, 'load')) {
                if ($return) {
                    return $CI->load->view($viewPath, $data, true);
                }
                $CI->load->view($viewPath, $data);
                return;
            }
        }
        
        // Fallback
        $fullPath = APPPATH . $viewPath . '.php';
        if (!file_exists($fullPath)) {
            show_error("Module view not found: {$viewPath}");
        }
        
        extract($data);
        if ($return) {
            ob_start();
            include($fullPath);
            return ob_get_clean();
        }
        
        include($fullPath);
    }
}

if (!function_exists('module_config')) {
    /**
     * Get module config value
     */
    function module_config(string $key, $default = null, string $module = '')
    {
        if (empty($module)) {
            $module = current_module();
        }
        
        if (empty($module)) {
            return $default;
        }
        
        $config = \Kodhe\Framework\Support\Modules::config($module, true);
        
        return $config[$key] ?? $default;
    }
}

if (!function_exists('module_info')) {
    /**
     * Get module information
     */
    function module_info(string $module = ''): array
    {
        if (empty($module)) {
            $module = current_module();
        }
        
        if (empty($module)) {
            return [];
        }
        
        if (method_exists('\Kodhe\Framework\Support\Modules', 'getModuleInfo')) {
            return \Kodhe\Framework\Support\Modules::getModuleInfo($module);
        }
        
        return [];
    }
}

if (!function_exists('module_exists')) {
    /**
     * Check if module exists
     */
    function module_exists(string $module): bool
    {
        return \Kodhe\Framework\Support\Modules::moduleExists($module);
    }
}

if (!function_exists('module_url')) {
    /**
     * Generate URL for module
     */
    function module_url(string $path = '', string $module = ''): string
    {
        if (empty($module)) {
            $module = current_module();
        }
        
        if (empty($module)) {
            return site_url($path);
        }
        
        $modulePath = "{$module}/{$path}";
        return site_url($modulePath);
    }
}

if (!function_exists('module_asset')) {
    /**
     * Get module asset URL
     */
    function module_asset(string $asset, string $module = ''): string
    {
        if (empty($module)) {
            $module = current_module();
        }
        
        if (empty($module)) {
            return base_url($asset);
        }
        
        $assetPath = "modules/{$module}/assets/{$asset}";
        return base_url($assetPath);
    }
}