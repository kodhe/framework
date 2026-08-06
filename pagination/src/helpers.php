<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Bootstrap 5 Pagination Helper - Manual Builder
 */

if (!function_exists('render_pagination')) {
    function render_pagination($base_url, $total_rows, $per_page = 10, $segment = 'page')
    {
        $CI = kodhe();
        
        // Get current page
        $current_page = (int) $CI->input->get($segment);
        if ($current_page < 1) $current_page = 1;
        
        // Calculate total pages
        $total_pages = (int) ceil($total_rows / $per_page);
        
        if ($total_pages <= 1) return '';
        
        // Build query string without page
        $gets = $_GET;
        unset($gets[$segment]);
        $query_string = !empty($gets) ? '&' . http_build_query($gets) : '';
        
        $output = '<nav aria-label="Page navigation"><ul class="pagination justify-content-end mb-0">';
        
        // First & Previous
        if ($current_page > 1) {
            $output .= '<li class="page-item"><a class="page-link" href="' . $base_url . '?' . $segment . '=1' . $query_string . '"><i class="bi bi-chevron-double-left"></i></a></li>';
            $output .= '<li class="page-item"><a class="page-link" href="' . $base_url . '?' . $segment . '=' . ($current_page - 1) . $query_string . '"><i class="bi bi-chevron-left"></i></a></li>';
        } else {
            $output .= '<li class="page-item disabled"><span class="page-link"><i class="bi bi-chevron-double-left"></i></span></li>';
            $output .= '<li class="page-item disabled"><span class="page-link"><i class="bi bi-chevron-left"></i></span></li>';
        }
        
        // Page numbers
        $start = max(1, $current_page - 2);
        $end = min($total_pages, $current_page + 2);
        
        if ($start > 1) {
            $output .= '<li class="page-item"><a class="page-link" href="' . $base_url . '?' . $segment . '=1' . $query_string . '">1</a></li>';
            if ($start > 2) {
                $output .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
        }
        
        for ($i = $start; $i <= $end; $i++) {
            if ($i == $current_page) {
                $output .= '<li class="page-item active" aria-current="page"><span class="page-link">' . $i . '</span></li>';
            } else {
                $output .= '<li class="page-item"><a class="page-link" href="' . $base_url . '?' . $segment . '=' . $i . $query_string . '">' . $i . '</a></li>';
            }
        }
        
        if ($end < $total_pages) {
            if ($end < $total_pages - 1) {
                $output .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
            $output .= '<li class="page-item"><a class="page-link" href="' . $base_url . '?' . $segment . '=' . $total_pages . $query_string . '">' . $total_pages . '</a></li>';
        }
        
        // Next & Last
        if ($current_page < $total_pages) {
            $output .= '<li class="page-item"><a class="page-link" href="' . $base_url . '?' . $segment . '=' . ($current_page + 1) . $query_string . '"><i class="bi bi-chevron-right"></i></a></li>';
            $output .= '<li class="page-item"><a class="page-link" href="' . $base_url . '?' . $segment . '=' . $total_pages . $query_string . '"><i class="bi bi-chevron-double-right"></i></a></li>';
        } else {
            $output .= '<li class="page-item disabled"><span class="page-link"><i class="bi bi-chevron-right"></i></span></li>';
            $output .= '<li class="page-item disabled"><span class="page-link"><i class="bi bi-chevron-double-right"></i></span></li>';
        }
        
        $output .= '</ul></nav>';
        
        return $output;
    }
}