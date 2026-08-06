<?php

// application/helpers/path_helper.php
if (!function_exists('load_file_case_insensitive')) {
    /**
     * Load file with case-insensitive directory resolution
     */
    function load_file_case_insensitive($file, $directory = '', $required = TRUE)
    {
        $paths = array(APPPATH, BASEPATH);
        
        foreach ($paths as $path) {
            $resolvedPath = resolve_path($path, $directory);
            $fullPath = $resolvedPath . '/' . $file;
            
            if (file_exists($fullPath)) {
                if ($required) {
                    require_once($fullPath);
                } else {
                    include_once($fullPath);
                }
                return TRUE;
            }
        }
        
        if ($required) {
            show_error('Unable to load file: ' . $file);
        }
        
        return FALSE;
    }
}