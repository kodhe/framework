<?php

declare(strict_types=1);

namespace Kodhe\Framework\Console;

/**
 * Console Output Formatter
 *
 * Parses inline style tags such as <info>text</info> into ANSI escape
 * sequences (or strips them when colors are disabled). Unknown tags are
 * left untouched so HTML-ish payloads never break the output.
 */
class Formatter
{
    /**
     * Style name => foreground color code.
     *
     * @var array<string, int>
     */
    protected static array $styles = [
        'info' => 36,
        'comment' => 32,
        'success' => 32,
        'warning' => 33,
        'error' => 31,
        'debug' => 90,
    ];

    /**
     * Register or override a named style with an ANSI foreground color code.
     */
    public static function addStyle(string $name, int $color): void
    {
        self::$styles[$name] = $color;
    }

    /**
     * Check whether a style name is registered.
     */
    public static function hasStyle(string $name): bool
    {
        return isset(self::$styles[$name]);
    }

    /**
     * Wrap a message in raw ANSI color codes.
     */
    public static function colorize(string $message, int $foreground, int $background = 0): string
    {
        if ($background > 0) {
            return sprintf("\033[%d;%dm%s\033[0m", $foreground, $background + 10, $message);
        }

        return sprintf("\033[%dm%s\033[0m", $foreground, $message);
    }

    /**
     * Parse <style>...</style> tags in a message.
     *
     * @param bool $decorated When false, recognized tags are stripped instead of colorized.
     */
    public static function format(string $message, bool $decorated = true): string
    {
        foreach (self::$styles as $name => $color) {
            $pattern = '/<' . preg_quote($name, '/') . '>(.*?)<\/' . preg_quote($name, '/') . '>/s';

            if ($decorated) {
                $message = preg_replace_callback($pattern, static function (array $m) use ($color): string {
                    return self::colorize($m[1], $color);
                }, $message);
            } else {
                $message = preg_replace($pattern, '$1', $message);
            }
        }

        return $message;
    }

    /**
     * Remove every HTML/XML-like tag from a message (used for length calculations).
     */
    public static function escape(string $message): string
    {
        return strip_tags($message);
    }
}
