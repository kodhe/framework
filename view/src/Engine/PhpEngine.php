<?php

declare(strict_types=1);

namespace Kodhe\View\Engine;

class PhpEngine implements EngineInterface
{
    protected $CI;
    protected $viewsPath;
    
    public function __construct($config = [])
    {
        $this->CI =& get_instance();

        $this->viewsPath = is_array($config) ? ($config['views_path'] ?? VIEWPATH) : VIEWPATH;
        $this->viewsPath = rtrim($this->viewsPath, '/') . '/';

        if (method_exists($this->CI->load, 'add_view_path')) {
            $this->CI->load->add_view_path($this->viewsPath);
        }
    }
    
    // Di PhpEngine.php, method render():
    public function render($view, $data = [])
    {
        if (method_exists($this->CI->load, 'prepend_view_path')) {
            $this->CI->load->prepend_view_path($this->viewsPath);
        }
        
        $templateData = get_template_assets();
        $allData = array_merge($templateData, $data);
        
        // ✅ Simpan dan hapus layout dari data
        $layout = null;
        if (!empty($allData['_layout'])) {
            $layout = $allData['_layout'];
            unset($allData['_layout']);  // 🔑 HAPUS dari data yang diteruskan
        }
        
        // Jika ada layout, render dengan layout
        if ($layout) {
            // ✅ Render content TANPA _layout
            $allData['content'] = $this->renderContent($view, $allData);
            
            // ✅ Render layout TANPA _layout (sudah di-unset)
            if (method_exists($this->CI->load, 'legacy_view')) {
                return $this->CI->load->legacy_view($layout, $allData, true);
            }
            return $this->CI->load->view($layout, $allData, true);
        }
        
        // ✅ Render biasa tanpa layout
        if (method_exists($this->CI->load, 'legacy_view')) {
            return $this->CI->load->legacy_view($view, $allData, true);
        }
        
        return $this->CI->load->view($view, $allData, true);
    }
    
    protected function renderContent($view, $data = [])
    {
        if (method_exists($this->CI->load, 'legacy_view')) {
            return $this->CI->load->legacy_view($view, $data, true);
        }
        return $this->CI->load->view($view, $data, true);
    }
    
    public function exists($view)
    {
        $view = $view . $this->getExtension();
        
        $fullPath = $this->viewsPath . ltrim($view, '/');
        if (file_exists($fullPath)) {
            return true;
        }
        
        if (method_exists($this->CI->load, 'get_view_paths')) {
            foreach ($this->CI->load->get_view_paths() as $path => $cascade) {
                if ($path === $this->viewsPath) {
                    continue;
                }
                $fullPath = rtrim($path, '/') . '/' . ltrim($view, '/');
                if (file_exists($fullPath)) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    public function getExtension()
    {
        return '.php';
    }
    
    public function setPath($path)
    {
        $this->viewsPath = rtrim($path, '/') . '/';
        
        if (method_exists($this->CI->load, 'add_view_path')) {
            $this->CI->load->add_view_path($this->viewsPath);
        }
        return $this;
    }
    
    public function getPath()
    {
        return $this->viewsPath;
    }
}
