<?php

declare(strict_types=1);

if (!function_exists('site_url')) {
    function site_url(string $uri = '', ?string $protocol = null): string
    {
        $baseUrl = rtrim(base_url(), '/');
        $uri = ltrim($uri, '/');
        
        if ($uri !== '') {
            return $baseUrl . '/' . $uri;
        }
        
        return $baseUrl;
    }
}

if (!function_exists('base_url')) {
    function base_url(?string $uri = ''): string
    {
        static $baseUrl;
        
        if ($baseUrl === null) {
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
            
            $basePath = dirname($scriptName);
            if ($basePath === '/' || $basePath === '\\') {
                $basePath = '';
            }
            
            $baseUrl = $protocol . '://' . $host . $basePath;
        }
        
        return $baseUrl . ($uri ? '/' . ltrim($uri, '/') : '');
    }
}

if (!function_exists('redirect')) {
    function redirect(string $uri = '', int $status = 302): void
    {
        if ($uri === '') {
            $uri = site_url();
        } elseif (strpos($uri, '://') === false) {
            $uri = site_url($uri);
        }
        
        header('Location: ' . $uri, true, $status);
        exit;
    }
}

if (!function_exists('current_url')) {
    function current_url(bool $returnObject = false)
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        
        $url = $protocol . '://' . $host . $uri;
        
        if ($returnObject) {
            return new \CodeIgniter\Http\Http\Uri(
                $protocol,
                explode(':', $host)[0],
                strpos($host, ':') !== false ? (int)explode(':', $host)[1] : null,
                parse_url($uri, PHP_URL_PATH) ?: '/',
                parse_url($uri, PHP_URL_QUERY) ?: ''
            );
        }
        
        return $url;
    }
}

if (!function_exists('anchor')) {
    function anchor(string $uri = '', string $title = '', array $attributes = []): string
    {
        $url = site_url($uri);
        
        if ($title === '') {
            $title = $url;
        }
        
        $attrString = '';
        foreach ($attributes as $key => $value) {
            $attrString .= ' ' . $key . '="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '"';
        }
        
        return '<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '"' . $attrString . '>' . $title . '</a>';
    }
}

if (!function_exists('safe_anchor')) {
    function safe_anchor(string $uri = '', string $title = '', array $attributes = []): string
    {
        return anchor($uri, $title, $attributes);
    }
}

if (!function_exists('popup_anchor')) {
    function popup_anchor(string $uri = '', string $title = '', array $attributes = []): string
    {
        $defaultAttributes = [
            'onclick' => "window.open(this.href, '_blank', 'width=800,height=600,scrollbars=yes,resizable=yes'); return false;"
        ];
        
        $mergedAttributes = array_merge($defaultAttributes, $attributes);
        
        return anchor($uri, $title, $mergedAttributes);
    }
}

if (!function_exists('mailto')) {
    function mailto(string $email, string $title = '', array $attributes = []): string
    {
        if ($title === '') {
            $title = $email;
        }
        
        $attrString = '';
        foreach ($attributes as $key => $value) {
            $attrString .= ' ' . $key . '="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '"';
        }
        
        return '<a href="mailto:' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . '"' . $attrString . '>' . $title . '</a>';
    }
}

if (!function_exists('safe_mailto')) {
    function safe_mailto(string $email, string $title = '', array $attributes = []): string
    {
        return mailto($email, $title, $attributes);
    }
}

if (!function_exists('auto_link')) {
    function auto_link(string $str, string $type = 'both', bool $popup = false): string
    {
        if ($type !== 'none') {
            if ($type === 'both' || $type === 'url') {
                $str = preg_replace_callback(
                    '#(\w+://[^\s<]+)#i',
                    function ($matches) use ($popup) {
                        $url = $matches[1];
                        $display = strlen($url) > 50 ? substr($url, 0, 47) . '...' : $url;
                        
                        if ($popup) {
                            return '<a href="' . $url . '" target="_blank" rel="noopener">' . $display . '</a>';
                        }
                        
                        return '<a href="' . $url . '">' . $display . '</a>';
                    },
                    $str
                );
            }
            
            if ($type === 'both' || $type === 'email') {
                $str = preg_replace_callback(
                    '#([\w\.\-]+@[\w\.\-]+\.[a-zA-Z]{2,6})#',
                    function ($matches) {
                        $email = $matches[1];
                        return '<a href="mailto:' . $email . '">' . $email . '</a>';
                    },
                    $str
                );
            }
        }
        
        return $str;
    }
}
