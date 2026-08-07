<?php

namespace Kodhe\Typography\Parsers;

use Kodhe\Typography\Contracts\ParserInterface;

/**
 * Parser untuk melindungi tag HTML saat proses formatting.
 */
class HtmlParser implements ParserInterface
{
    /**
     * @var array Token yang dilindungi
     */
    private array $protectedTags = [];

    /**
     * @var int Counter untuk token
     */
    private int $tokenCounter = 0;

    /**
     * Parse konten dan lindungi tag HTML.
     */
    public function parse(string $content): array
    {
        $this->protectedTags = [];
        $this->tokenCounter = 0;

        // Lindungi tag <pre> dan isinya
        $content = $this->protectPreTags($content);
        
        // Lindungi tag HTML lainnya
        $content = $this->protectHtmlTags($content);

        return [
            'content' => $content,
            'protected' => $this->protectedTags
        ];
    }

    /**
     * Restore tag HTML yang dilindungi.
     */
    public function restore(string $content, array $data): string
    {
        if (!isset($data['protected']) || empty($data['protected'])) {
            return $content;
        }

        // Kembalikan tag yang dilindungi
        foreach ($data['protected'] as $token => $tag) {
            $content = str_replace($token, $tag, $content);
        }

        return $content;
    }

    /**
     * Lindungi tag <pre>.
     */
    private function protectPreTags(string $content): string
    {
        return preg_replace_callback(
            '/<pre.*?>.*?<\/pre>/is',
            function ($matches) {
                $token = '{PRE_TAG_' . $this->tokenCounter++ . '}';
                $this->protectedTags[$token] = $matches[0];
                return $token;
            },
            $content
        );
    }

    /**
     * Lindungi tag HTML lainnya.
     */
    private function protectHtmlTags(string $content): string
    {
        return preg_replace_callback(
            '/<[^>]+>/i',
            function ($matches) {
                $token = '{HTML_TAG_' . $this->tokenCounter++ . '}';
                $this->protectedTags[$token] = $matches[0];
                return $token;
            },
            $content
        );
    }
}
