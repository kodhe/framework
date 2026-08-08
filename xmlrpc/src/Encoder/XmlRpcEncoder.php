<?php

declare(strict_types=1);

namespace Kodhe\Framework\Xmlrpc\Encoder;

use Kodhe\Framework\Xmlrpc\Contracts\EncoderInterface;
use Kodhe\Framework\Xmlrpc\Support\LazyEncoder;
use Kodhe\Framework\Xmlrpc\ValueObjects\XmlRpcValue;

/**
 * XML-RPC encoder implementation using Strategy pattern
 */
class XmlRpcEncoder implements EncoderInterface
{
    /**
     * @var array
     */
    private $xmlrpcTypes = [
        'i4' => 1,
        'int' => 1,
        'boolean' => 1,
        'string' => 1,
        'double' => 1,
        'dateTime.iso8601' => 1,
        'base64' => 1,
        'array' => 2,
        'struct' => 3,
    ];

    /**
     * Encode a value to XML-RPC format
     *
     * @param mixed $value
     * @return string
     */
    public function encode($value): string
    {
        if ($value instanceof XmlRpcValue) {
            return $this->encodeValue($value);
        }

        return $this->encodeValue(new XmlRpcValue($value));
    }

    /**
     * Encode an XmlRpcValue object
     *
     * @param XmlRpcValue $value
     * @return string
     */
    private function encodeValue(XmlRpcValue $value): string
    {
        if ($value->isScalar()) {
            return LazyEncoder::encode($value->getValue(), $value->getType());
        } elseif ($value->isArray()) {
            return $this->encodeArray($value->getValue());
        } elseif ($value->isStruct()) {
            return $this->encodeStruct($value->getValue());
        }

        return $this->encodeScalar($value->getValue(), 'string');
    }

    /**
     * Encode array value
     *
     * @param array $values
     * @return string
     */
    private function encodeArray(array $values): string
    {
        $result = "<array>\n<data>\n";

        foreach ($values as $value) {
            $result .= $this->encode($value);
        }

        $result .= "</data>\n</array>\n";

        return $result;
    }

    /**
     * Encode struct value
     *
     * @param array $struct
     * @return string
     */
    private function encodeStruct(array $struct): string
    {
        $result = "<struct>\n";

        foreach ($struct as $key => $value) {
            $result .= "<member>\n<name>{$key}</name>\n";
            $result .= $this->encode($value);
            $result .= "</member>\n";
        }

        $result .= '</struct>';

        return $result;
    }

    /**
     * Encode scalar value
     *
     * @param mixed $value
     * @param string $type
     * @return string
     */
    private function encodeScalar($value, string $type): string
    {
        return LazyEncoder::encode($value, $type);
    }

    /**
     * Create XML-RPC message payload
     *
     * @param string $methodName
     * @param array $params
     * @return string
     */
    public function createPayload(string $methodName, array $params = []): string
    {
        $payload = '<?xml version="1.0"?>'."\r\n"
            ."<methodCall>\r\n"
            .'<methodName>'.$methodName."</methodName>\r\n"
            ."<params>\r\n";

        foreach ($params as $param) {
            $encoded = $this->encode($param);
            $payload .= "<param>\r\n{$encoded}</param>\r\n";
        }

        $payload .= "</params>\r\n</methodCall>\r\n";

        return $payload;
    }
}
