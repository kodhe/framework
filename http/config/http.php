<?php

return [
    "default_protocol" => "HTTP/1.1",
    "charset" => "UTF-8",
    "status_codes" => [
        200 => "OK", 201 => "Created", 204 => "No Content",
        301 => "Moved Permanently", 302 => "Found", 303 => "See Other",
        304 => "Not Modified", 307 => "Temporary Redirect", 308 => "Permanent Redirect",
        400 => "Bad Request", 401 => "Unauthorized", 403 => "Forbidden",
        404 => "Not Found", 405 => "Method Not Allowed", 422 => "Unprocessable Entity",
        429 => "Too Many Requests", 500 => "Internal Server Error",
        502 => "Bad Gateway", 503 => "Service Unavailable", 504 => "Gateway Timeout",
    ],
    "middleware_groups" => ["web" => [], "api" => [], "auth" => []],
    "global_middleware" => [],
];
