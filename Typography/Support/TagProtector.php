<?php

namespace Kodhe\Typography\Support;

/**
 * Helper untuk melindungi tag HTML dan braced quotes.
 */
class TagProtector
{
    /**
     * @var array Token yang dilindungi
     */
    private array $protectedItems = [];

    /**
     * @var int Counter token
     */
    private int $tokenCounter = 0;

    /**
     * Lindungi tanda kutip dalam kurung kurawal.
     *
     * @param string $str Teks input
     * @param array $tempSwap Swap sementara
     * @return array ['content' => string, 'protected' => array]
     */
    public function protectBracedQuotes(string $str, array $tempSwap = []): array
    {
        $this->protectedItems = $tempSwap;
        $this->tokenCounter = 0;

        // Lindungi tanda kutip dalam {php} tags
        $str = preg_replace_callback(
            '/\{.+?\}/s',
            function ($matches) {
                return $this->protectContent($matches[0]);
            },
            $str
        );

        return [
            'content' => $str,
            'protected' => $this->protectedItems
        ];
    }

    /**
     * Restore tanda kutip yang dilindungi.
     *
     * @param string $str Teks yang sudah diproses
     * @param array $protected Data yang dilindungi
     * @return string Teks yang sudah direstore
     */
    public function restoreBracedQuotes(string $str, array $protected): string
    {
        foreach ($protected as $token => $original) {
            $str = str_replace($token, $original, $str);
        }

        return $str;
    }

    /**
     * Lindungi konten dengan token.
     *
     * @param string $content Konten yang akan dilindungi
     * @return string Token
     */
    private function protectContent(string $content): string
    {
        $token = '{PROTECTED_' . $this->tokenCounter++ . '}';
        $this->protectedItems[$token] = $content;
        return $token;
    }
}
