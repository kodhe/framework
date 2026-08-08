<?php

declare(strict_types=1);

namespace Kodhe\Framework\Xmlrpc\Message;

use Kodhe\Framework\Xmlrpc\ValueObjects\XmlRpcValue;
use Kodhe\Framework\Xmlrpc\Factory\EncoderFactory;
use Kodhe\Framework\Xmlrpc\Contracts\EncoderInterface;

/**
 * XML-RPC Message class
 */
class XmlRpcMessage
{
    /**
     * @var string
     */
    public $payload = '';

    /**
     * @var string
     */
    private $methodName;

    /**
     * @var array
     */
    private $params = [];

    /**
     * @var EncoderInterface
     */
    private $encoder;

    /**
     * @var bool
     */
    public $debug = false;

    /**
     * Constructor
     *
     * @param string $method
     * @param array $params
     * @param EncoderInterface|null $encoder
     */
    public function __construct(string $method, array $params = [], ?EncoderInterface $encoder = null)
    {
        $this->methodName = $method;
        $this->params = $params;
        $this->encoder = $encoder ?? EncoderFactory::create();
    }

    /**
     * Create the XML payload
     *
     * @return void
     */
    public function createPayload(): void
    {
        $this->payload = $this->encoder->createPayload($this->methodName, $this->params);
    }

    /**
     * Add a parameter to the message
     *
     * @param mixed $param
     * @return void
     */
    public function addParam($param): void
    {
        if (!$param instanceof XmlRpcValue) {
            $param = new XmlRpcValue($param);
        }
        $this->params[] = $param;
    }

    /**
     * Get the method name
     *
     * @return string
     */
    public function getMethodName(): string
    {
        return $this->methodName;
    }

    /**
     * Get the parameters
     *
     * @return array
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * Get the payload
     *
     * @return string
     */
    public function getPayload(): string
    {
        if (empty($this->payload)) {
            $this->createPayload();
        }
        return $this->payload;
    }
}
