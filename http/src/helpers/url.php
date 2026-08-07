<?php

/**
 * URL Helper Functions
 * 
 * Provides helper functions for URL generation and manipulation
 */

use Kodhe\Framework\Http\Http\Uri;
use Kodhe\Framework\Http\Http\Request;
use Kodhe\Framework\Http\Http\Response;
use Kodhe\Framework\Http\Http\RedirectResponse;
use Kodhe\Framework\Http\Http\JsonResponse;

if (!function_exists('site_url')) {
    /**
     * Generate a site URL
     */
    function site_url(string $uri = '', ?string $protocol = null): string
    {
        $base = base_url();
        
        if ($uri !== '') {
            $uri = ltrim($uri, '/');
            $base .= $uri;
        }
        
        return $base;
    }
}

if (!function_exists('base_url')) {
    /**
     * Get the base URL
     */
    function base_url(?string $protocol = null): string
    {
        $isSecure = (
            (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ||
            (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
            (isset($_SERVER['REQUEST_SCHEME']) && $_SERVER['REQUEST_SCHEME'] === 'https')
        );
        
        $scheme = $protocol ?? ($isSecure ? 'https' : 'http');
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        
        return "{$scheme}://{$host}/";
    }
}

if (!function_exists('current_url')) {
    /**
     * Get the current URL
     */
    function current_url(bool $returnObject = false)
    {
        $uri = new Uri(
            (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') .
            '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') .
            ($_SERVER['REQUEST_URI'] ?? '/')
        );
        
        return $returnObject ? $uri : (string) $uri;
    }
}

if (!function_exists('previous_url')) {
    /**
     * Get the previous URL (referrer)
     */
    function previous_url(): ?string
    {
        return $_SERVER['HTTP_REFERER'] ?? null;
    }
}

if (!function_exists('redirect')) {
    /**
     * Redirect to a URL
     */
    function redirect(string $uri = '', int $status = 302): RedirectResponse
    {
        if ($uri === '') {
            $uri = site_url();
        }
        
        return new RedirectResponse(site_url($uri), $status);
    }
}

if (!function_exists('response')) {
    /**
     * Create a response
     */
    function response(string $body = '', int $status = 200, array $headers = []): Response
    {
        return new Response($status, $headers, $body);
    }
}

if (!function_exists('json_response')) {
    /**
     * Create a JSON response
     */
    function json_response($data, int $status = 200, array $headers = []): JsonResponse
    {
        return new JsonResponse($data, $status, $headers);
    }
}

if (!function_exists('request')) {
    /**
     * Get the current request or create a new one
     */
    function request(?array $server = null, ?array $get = null, ?array $post = null, ?array $cookie = null): Request
    {
        return Request::createFromGlobals(
            $server ?? $_SERVER,
            $get ?? $_GET,
            $post ?? $_POST,
            $cookie ?? $_COOKIE
        );
    }
}

if (!function_exists('uri_string')) {
    /**
     * Get the URI string
     */
    function uri_string(): string
    {
        return $_SERVER['REQUEST_URI'] ?? '/';
    }
}

if (!function_exists('index_page')) {
    /**
     * Get the index page
     */
    function index_page(): string
    {
        return ''; // Typically empty in modern setups
    }
}

if (!function_exists('anchor')) {
    /**
     * Create an anchor link
     */
    function anchor(
        string $uri = '',
        string $title = '',
        array $attributes = [],
        ?string $protocol = null
    ): string {
        $url = site_url($uri, $protocol);
        $title = $title ?: $url;
        
        $attrString = '';
        foreach ($attributes as $key => $value) {
            $attrString .= " {$key}=\"{$value}\"";
        }
        
        return "<a href=\"{$url}\"{$attrString}>{$title}</a>";
    }
}

if (!function_exists('safe_mailto')) {
    /**
     * Create a safe mailto link
     */
    function safe_mailto(
        string $email,
        string $title = '',
        array $attributes = []
    ): string {
        $title = $title ?: $email;
        
        $attrString = '';
        foreach ($attributes as $key => $value) {
            $attrString .= " {$key}=\"{$value}\"";
        }
        
        // Simple obfuscation
        $obfuscated = str_replace('@', ' [at] ', $email);
        
        return "<a href=\"mailto:{$email}\"{$attrString}>{$obfuscated}</a>";
    }
}

if (!function_exists('popup_anchor')) {
    /**
     * Create a popup anchor link
     */
    function popup_anchor(
        string $uri = '',
        string $title = '',
        array $attributes = []
    ): string {
        $url = site_url($uri);
        $title = $title ?: $url;
        
        $width = $attributes['width'] ?? 800;
        $height = $attributes['height'] ?? 600;
        
        $js = "window.open(this.href, '_blank', 'width={$width},height={$height}'); return false;";
        
        return "<a href=\"{$url}\" onclick=\"{$js}\">{$title}</a>";
    }
}
