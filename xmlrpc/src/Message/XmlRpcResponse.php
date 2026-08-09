<?php

declare(strict_types=1);

namespace Kodhe\Framework\Xmlrpc\Message;

use Kodhe\Framework\Xmlrpc\ValueObjects\XmlRpcValue;

/**
 * XML-RPC Response class
 */
class XmlRpcResponse
{
    /**
     * @var mixed
     */
    public $val = 0;

    /**
     * @var int
     */
    public $errno = 0;

    /**
     * @var string
     */
    public $errstr = '';

    /**
     * @var array
     */
    public $headers = [];

    /**
     * @var bool
     */
    public $xss_clean = true;

    /**
     * Constructor
     *
     * @param mixed $val
     * @param int $code
     * @param string $fstr
     */
    public function __construct($val, $code = 0, $fstr = '')
    {
        if ($code !== 0) {
            // error
            $this->errno = $code;
            $this->errstr = htmlspecialchars($fstr, ENT_XML1 | ENT_NOQUOTES, 'UTF-8');
        } elseif (!is_object($val)) {
            // programmer error, not an object
            error_log("Invalid type '".gettype($val)."' (value: {$val}) passed to XML_RPC_Response. Defaulting to empty value.");
            $this->val = new XmlRpcValue();
        } else {
            $this->val = $val;
        }
    }

    /**
     * Fault code
     *
     * @return int
     */
    public function faultCode(): int
    {
        return $this->errno;
    }

    /**
     * Fault string
     *
     * @return string
     */
    public function faultString(): string
    {
        return $this->errstr;
    }

    /**
     * Value
     *
     * @return mixed
     */
    public function value()
    {
        return $this->val;
    }

    /**
     * Prepare response
     *
     * @return string
     */
    public function prepare_response(): string
    {
        return "<methodResponse>\n"
            .($this->errno
                ? '<fault>
    <value>
        <struct>
            <member>
                <name>faultCode</name>
                <value><int>'.$this->errno.'</int></value>
            </member>
            <member>
                <name>faultString</name>
                <value><string>'.$this->errstr.'</string></value>
            </member>
        </struct>
    </value>
</fault>'
                : "<params>\n<param>\n".$this->val->serialize_class()."</param>\n</params>")
            ."\n</methodResponse>";
    }

    /**
     * Decode response
     *
     * @param mixed $array
     * @return mixed
     */
    public function decode($array = null)
    {
        if (is_array($array)) {
            foreach ($array as $key => &$value) {
                if (is_array($value)) {
                    $array[$key] = $this->decode($value);
                }
            }

            return $array;
        }

        return $this->val;
    }
}
