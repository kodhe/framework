<?php

declare(strict_types=1);

namespace Kodhe\Trackback\Client;

use Kodhe\Trackback\Contracts\TransportInterface;
use Kodhe\Trackback\Contracts\ParserInterface;
use Kodhe\Trackback\Support\TrackbackConfig;
use Kodhe\Trackback\Support\UrlValidator;
use Kodhe\Trackback\Support\ResponseValidator;
use Kodhe\Trackback\Factory\TransportFactory;
use Kodhe\Trackback\Parser\TrackbackParser;
use Kodhe\Trackback\Exceptions\TrackbackSendException;
use Kodhe\Trackback\Exceptions\InvalidUrlException;

/**
 * Trackback client for sending trackbacks.
 */
class TrackbackClient
{
    private TransportInterface $transport;
    private ParserInterface $parser;
    private UrlValidator $urlValidator;
    private ResponseValidator $responseValidator;
    private TrackbackConfig $config;
    private array $errors = [];

    public function __construct(
        ?TransportInterface $transport = null,
        ?ParserInterface $parser = null,
        ?TrackbackConfig $config = null
    ) {
        $this->config = $config ?? new TrackbackConfig();
        $this->transport = $transport ?? TransportFactory::getDefault();
        $this->parser = $parser ?? new TrackbackParser($this->config);
        $this->urlValidator = new UrlValidator($this->config);
        $this->responseValidator = new ResponseValidator();
    }

    /**
     * Send trackback to one or more URLs.
     *
     * @param array $tb_data Trackback data
     * @return bool TRUE on success (all URLs), FALSE on any failure
     */
    public function send(array $tb_data): bool
    {
        if (!is_array($tb_data)) {
            $this->addError('The send() method must be passed an array');
            return false;
        }

        // Validate required fields
        $required = ['url', 'title', 'excerpt', 'blog_name', 'ping_url'];
        foreach ($required as $field) {
            if (!isset($tb_data[$field])) {
                $this->addError('Required item missing: ' . $field);
                return false;
            }
        }

        // Process data
        $url = $this->processField($tb_data['url'], 'url');
        $title = $this->processField($tb_data['title'], 'title');
        $excerpt = $this->processField($tb_data['excerpt'], 'excerpt');
        $blogName = $this->processField($tb_data['blog_name'], 'blog_name');
        $charset = $tb_data['charset'] ?? $this->config->getCharset();

        // Extract and validate ping URLs
        $pingUrls = $this->extractAndValidateUrls($tb_data['ping_url']);

        if (empty($pingUrls)) {
            $this->addError('No valid ping URLs provided');
            return false;
        }

        // Build POST data
        $postData = $this->parser->buildPostData([
            'url' => $url,
            'title' => $title,
            'blog_name' => $blogName,
            'excerpt' => $excerpt,
            'charset' => $charset,
        ]);

        // Send to each URL
        $allSuccess = true;
        foreach ($pingUrls as $pingUrl) {
            if (!$this->sendToUrl($pingUrl, $postData)) {
                $allSuccess = false;
            }
        }

        return $allSuccess;
    }

    /**
     * Process a field value (strip tags, convert XML, etc.).
     */
    private function processField(string $value, string $field): string
    {
        $value = stripslashes($value);
        $value = strip_tags($value);
        
        if ($field === 'url') {
            $value = str_replace('&#45;', '-', $this->parser->convertXml($value));
        } elseif ($field === 'excerpt') {
            $value = $this->parser->limitCharacters($this->parser->convertXml($value));
        } else {
            $value = $this->parser->convertXml($value);
        }

        // Convert high ASCII if enabled
        if ($this->config->isConvertAscii() && in_array($field, ['excerpt', 'title', 'blog_name'], true)) {
            $value = $this->parser->convertAscii($value);
        }

        return $value;
    }

    /**
     * Extract and validate URLs from ping_url field.
     */
    private function extractAndValidateUrls(string $urlsString): array
    {
        $extracted = $this->parser->extractUrls($urlsString);
        $validated = [];

        foreach ($extracted as $url) {
            try {
                $validated[] = $this->urlValidator->validate($url);
            } catch (InvalidUrlException $e) {
                $this->addError('Invalid URL: ' . $url . ' - ' . $e->getMessage());
            }
        }

        return $validated;
    }

    /**
     * Send trackback to a single URL.
     */
    private function sendToUrl(string $url, string $data): bool
    {
        try {
            $result = $this->transport->send($url, $data);
            
            if (!$result['success']) {
                $parsed = $this->responseValidator->validate($result['body']);
                $this->addError($parsed['message']);
                return false;
            }

            return true;
        } catch (\Exception $e) {
            $this->addError('Failed to send to ' . $url . ': ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Add an error message.
     */
    private function addError(string $message): void
    {
        $this->errors[] = $message;
        
        // Log error if CI log_message function exists
        if (function_exists('log_message')) {
            log_message('error', $message);
        }
    }

    /**
     * Get all error messages.
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Clear all errors.
     */
    public function clearErrors(): void
    {
        $this->errors = [];
    }

    /**
     * Set the transport instance.
     */
    public function setTransport(TransportInterface $transport): self
    {
        $this->transport = $transport;
        return $this;
    }

    /**
     * Set the parser instance.
     */
    public function setParser(ParserInterface $parser): self
    {
        $this->parser = $parser;
        return $this;
    }
}
