<?php namespace Kodhe\Framework\View\Engine;

use Kodhe\Framework\Config\Loaders\ViewLoader;

class PhpEngine implements EngineInterface
{
    protected $CI;
    protected $viewsPath;
    
    public function __construct($config = [])
    {
        
        $this->CI =& get_instance();

        $this->viewsPath = is_array($config) ? ($config['views_path'] ?? VIEWPATH) : VIEWPATH;
        $this->viewsPath = rtrim($this->viewsPath, '/') . '/';

        // Tambahkan path ke loader (bukan ganti semua)
        $this->CI->load->add_view_path($this->viewsPath);
    }
    
    public function render($view, $data = [])
    {
        // Tambahkan path saat ini di awal pencarian
        $this->CI->load->prepend_view_path($this->viewsPath);
        
        return $this->CI->load->legacy_view($view, $data, true);
    }
    
    public function exists($view)
    {
        $view = $view . $this->getExtension();
        
        // Cek di path khusus ini dulu
        $fullPath = $this->viewsPath . ltrim($view, '/');
        if (file_exists($fullPath)) {
            return true;
        }
        
        // Jika tidak ada, cek di semua paths loader
        foreach ($this->CI->load->get_view_paths() as $path => $cascade) {
            if ($path === $this->viewsPath) {
                continue; // Sudah dicek
            }
            $fullPath = rtrim($path, '/') . '/' . ltrim($view, '/');
            if (file_exists($fullPath)) {
                return true;
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
        
        // Tambahkan path baru ke loader
        $this->CI->load->add_view_path($this->viewsPath);
        return $this;
    }
    
    public function getPath()
    {
        return $this->viewsPath;
    }
}