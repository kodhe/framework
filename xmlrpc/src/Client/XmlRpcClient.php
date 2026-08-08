<?php

declare(strict_types=0);

namespace Kodhe\Framework\Xmlrpc\Client;

use Kodhe\Framework\Xmlrpc\Contracts\TransportInterface;
use Kodhe\Framework\Xmlrpc\Contracts\EncoderInterface;
use Kodhe\Framework\Xmlrpc\Contracts\DecoderInterface;
use Kodhe\Framework\Xmlrpc\Factory\TransportFactory;
use Kodhe\Framework\Xmlrpc\Factory\EncoderFactory;
use Kodhe\Framework\Xmlrpc\Factory\DecoderFactory;
use Kodhe\Framework\Xmlrpc\Exceptions\TransportException;
use Kodhe\Framework\Xmlrpc\Exceptions\FaultException;
use Kodhe\Framework\Xmlrpc\Exceptions\XmlParseException;

/**
 * XML-RPC Client using Dependency Injection pattern
 */
class XmlRpcClient
{
    /**
     * @var TransportInterface
     */
    private $transport;

    /**
     * @var EncoderInterface
     */
    private $encoder;

    /**
     * @var DecoderInterface
     */
    private $decoder;

    /**
     * @var string
     */
    private $serverUrl;

    /**
     * @var int
     */
    private $port;

    /**
     * @var int
     */
    private $timeout;

    /**
     * @var bool
     */
    private $debug = false;

    /**
     * @var string
     */
    private $lastError = '';

    /**
     * Constructor with dependency injection
     *
     * @param TransportInterface|null $transport
     * @param EncoderInterface|null $encoder
     * @param DecoderInterface|null $decoder
     */
    public function __construct(
        ?TransportInterface $transport = null,
        ?EncoderInterface $encoder = null,
        ?DecoderInterface $decoder = null
    ) {
        $this->transport = $transport ?? TransportFactory::create();
        $this->encoder = $encoder ?? EncoderFactory::create();
        $this->decoder = $decoder ?? DecoderFactory::create();
    }

    /**
     * Set the server URL
     *
     * @param string $url
     * @param int $port
     * @return self
     */
    public function setServer(string $url, int $port = 80): self
    {
        if (stripos($url, 'http') !== 0) {
            $url = 'http://'.$url;
        }

        $parts = parse_url($url);
        $host = $parts['host'] ?? $url;
        $path = $parts['path'] ?? '/';

        if (isset($parts['user'], $parts['pass'])) {
            $host = $parts['user'].':'.$parts['pass'].'@'.$host;
        }

        if (!empty($parts['query'])) {
            $path .= '?'.$parts['query'];
        }

        $this->serverUrl = 'http://'.$host.$path;
        $this->port = $port;

        return $this;
    }

    /**
     * Set timeout
     *
     * @param int $seconds
     * @return self
     */
    public function setTimeout(int $seconds): self
    {
        $this->timeout = $seconds;
        return $this;
    }

    /**
     * Enable/disable debug mode
     *
     * @param bool $flag
     * @return self
     */
    public function setDebug(bool $flag = true): self
    {
        $this->debug = $flag;
        return $this;
    }

    /**
     * Call a remote method
     *
     * @param string $method
     * @param array $params
     * @return mixed
     * @throws FaultException
     * @throws TransportException
     * @throws XmlParseException
     */
    public function call(string $method, array $params = [])
    {
        $payload = $this->encoder->createPayload($method, $params);

        if ($this->debug) {
            echo "<pre>---REQUEST---\n".htmlspecialchars($payload)."\n---END REQUEST---\n\n</pre>";
        }

        $response = $this->transport->send($payload, $this->serverUrl, $this->port, $this->timeout);

        if ($this->debug) {
            echo "<pre>---RESPONSE---\n".htmlspecialchars($response['raw'])."\n---END RESPONSE---\n\n</pre>";
        }

        // Check for HTTP errors
        if (!empty($response['headers'])) {
            $firstHeader = $response['headers'][0] ?? '';
            if (strpos($firstHeader, 'HTTP') === 0 && !preg_match('/^HTTP\/[0-9\.]+ 200 /', $firstHeader)) {
                $errstr = substr($firstHeader, 0, strpos($firstHeader, "\n") - 1);
                throw new TransportException("Did not receive a '200 OK' response from remote server. ({$errstr})");
            }
        }

        // Parse the response
        try {
            $decoded = $this->decoder->decode($response['body']);
        } catch (XmlParseException $e) {
            $this->lastError = $e->getMessage();
            throw $e;
        }

        // Check for fault
        if (isset($decoded['isf']) && $decoded['isf'] > 0) {
            $faultCode = 0;
            $faultString = '';

            if (isset($decoded['value']) && is_object($decoded['value'])) {
                $kind = $decoded['value']->kindOf();
                if ($kind === 'struct') {
                    $faultCode = $decoded['value']->me['struct']['faultCode']->scalarval() ?? 0;
                    $faultString = $decoded['value']->me['struct']['faultString']->scalarval() ?? 'Unknown fault';
                }
            }

            $exception = new FaultException($faultCode, $faultString);
            $this->lastError = $faultString;
            throw $exception;
        }

        return $decoded['value'] ?? null;
    }

    /**
     * Get last error message
     *
     * @return string
     */
    public function getLastError(): string
    {
        return $this->lastError;
    }

    /**
     * Get the encoder
     *
     * @return EncoderInterface
     */
    public function getEncoder(): EncoderInterface
    {
        return $this->encoder;
    }

    /**
     * Get the decoder
     *
     * @return DecoderInterface
     */
    public function getDecoder(): DecoderInterface
    {
        return $this->decoder;
    }

    /**
     * Get the transport
     *
     * @return TransportInterface
     */
    public function getTransport(): TransportInterface
    {
        return $this->transport;
    }
}
