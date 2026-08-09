<?php

declare(strict_types=1);

namespace Kodhe\Framework\Xmlrpc;

use Kodhe\Framework\Xmlrpc\Client\XmlRpcClient;
use Kodhe\Framework\Xmlrpc\Message\XmlRpcMessage;
use Kodhe\Framework\Xmlrpc\Message\XmlRpcResponse;
use Kodhe\Framework\Xmlrpc\ValueObjects\XmlRpcValue;
use Kodhe\Framework\Xmlrpc\Factory\EncoderFactory;
use Kodhe\Framework\Xmlrpc\Factory\DecoderFactory;
use Kodhe\Framework\Xmlrpc\Factory\TransportFactory;
use Kodhe\Framework\Xmlrpc\Exceptions\XmlRpcException;
use Kodhe\Framework\Xmlrpc\Exceptions\FaultException;
use Kodhe\Framework\Xmlrpc\Exceptions\TransportException;
use Kodhe\Framework\Xmlrpc\Exceptions\XmlParseException;

/**
 * XML-RPC request handler class - Backward Compatible CI3 API
 *
 * @package     Kodhe\Xmlrpc
 * @category    XML-RPC
 */
class Xmlrpc
{
    /**
     * Debug flag
     *
     * @var bool
     */
    public $debug = false;

    /**
     * I4 data type
     *
     * @var string
     */
    public $xmlrpcI4 = 'i4';

    /**
     * Integer data type
     *
     * @var string
     */
    public $xmlrpcInt = 'int';

    /**
     * Boolean data type
     *
     * @var string
     */
    public $xmlrpcBoolean = 'boolean';

    /**
     * Double data type
     *
     * @var string
     */
    public $xmlrpcDouble = 'double';

    /**
     * String data type
     *
     * @var string
     */
    public $xmlrpcString = 'string';

    /**
     * DateTime format
     *
     * @var string
     */
    public $xmlrpcDateTime = 'dateTime.iso8601';

    /**
     * Base64 data type
     *
     * @var string
     */
    public $xmlrpcBase64 = 'base64';

    /**
     * Array data type
     *
     * @var string
     */
    public $xmlrpcArray = 'array';

    /**
     * Struct data type
     *
     * @var string
     */
    public $xmlrpcStruct = 'struct';

    /**
     * Data types list
     *
     * @var array
     */
    public $xmlrpcTypes = [];

    /**
     * Valid parents list
     *
     * @var array
     */
    public $valid_parents = [];

    /**
     * Response error numbers list
     *
     * @var array
     */
    public $xmlrpcerr = [];

    /**
     * Response error messages list
     *
     * @var string[]
     */
    public $xmlrpcstr = [];

    /**
     * Encoding charset
     *
     * @var string
     */
    public $xmlrpc_defencoding = 'UTF-8';

    /**
     * XML-RPC client name
     *
     * @var string
     */
    public $xmlrpcName = 'XML-RPC for CodeIgniter';

    /**
     * XML-RPC version
     *
     * @var string
     */
    public $xmlrpcVersion = '1.1';

    /**
     * Start of user errors
     *
     * @var int
     */
    public $xmlrpcerruser = 800;

    /**
     * Start of XML parse errors
     *
     * @var int
     */
    public $xmlrpcerrxml = 100;

    /**
     * Backslash replacement value
     *
     * @var string
     */
    public $xmlrpc_backslash = '';

    /**
     * XML-RPC Client object (backward compatible)
     *
     * @var XmlRpcClient|mixed
     */
    public $client;

    /**
     * XML-RPC Method name
     *
     * @var string
     */
    public $method;

    /**
     * XML-RPC Data
     *
     * @var array
     */
    public $data;

    /**
     * XML-RPC Message
     *
     * @var string
     */
    public $message = '';

    /**
     * Request error message
     *
     * @var string
     */
    public $error = '';

    /**
     * XML-RPC result object
     *
     * @var mixed
     */
    public $result;

    /**
     * XML-RPC Response
     *
     * @var array
     */
    public $response = [];

    /**
     * XSS Filter flag
     *
     * @var bool
     */
    public $xss_clean = true;

    // --------------------------------------------------------------------

    /**
     * Constructor
     *
     * Initializes property default values
     *
     * @param array $config
     * @return void
     */
    public function __construct($config = [])
    {
        $this->xmlrpc_backslash = chr(92).chr(92);

        // Types for info sent back and forth
        $this->xmlrpcTypes = [
            $this->xmlrpcI4 => 1,
            $this->xmlrpcInt => 1,
            $this->xmlrpcBoolean => 1,
            $this->xmlrpcString => 1,
            $this->xmlrpcDouble => 1,
            $this->xmlrpcDateTime => 1,
            $this->xmlrpcBase64 => 1,
            $this->xmlrpcArray => 2,
            $this->xmlrpcStruct => 3,
        ];

        // Array of Valid Parents for Various XML-RPC elements
        $this->valid_parents = [
            'BOOLEAN' => ['VALUE'],
            'I4' => ['VALUE'],
            'INT' => ['VALUE'],
            'STRING' => ['VALUE'],
            'DOUBLE' => ['VALUE'],
            'DATETIME.ISO8601' => ['VALUE'],
            'BASE64' => ['VALUE'],
            'ARRAY' => ['VALUE'],
            'STRUCT' => ['VALUE'],
            'PARAM' => ['PARAMS'],
            'METHODNAME' => ['METHODCALL'],
            'PARAMS' => ['METHODCALL', 'METHODRESPONSE'],
            'MEMBER' => ['STRUCT'],
            'NAME' => ['MEMBER'],
            'DATA' => ['ARRAY'],
            'FAULT' => ['METHODRESPONSE'],
            'VALUE' => ['MEMBER', 'DATA', 'PARAM', 'FAULT'],
        ];

        // XML-RPC Responses
        $this->xmlrpcerr['unknown_method'] = '1';
        $this->xmlrpcstr['unknown_method'] = 'This is not a known method for this XML-RPC Server';
        $this->xmlrpcerr['invalid_return'] = '2';
        $this->xmlrpcstr['invalid_return'] = 'The XML data received was either invalid or not in the correct form for XML-RPC. Turn on debugging to examine the XML data further.';
        $this->xmlrpcerr['incorrect_params'] = '3';
        $this->xmlrpcstr['incorrect_params'] = 'Incorrect parameters were passed to method';
        $this->xmlrpcerr['introspect_unknown'] = '4';
        $this->xmlrpcstr['introspect_unknown'] = 'Cannot inspect signature for request: method unknown';
        $this->xmlrpcerr['http_error'] = '5';
        $this->xmlrpcstr['http_error'] = "Did not receive a '200 OK' response from remote server.";
        $this->xmlrpcerr['no_data'] = '6';
        $this->xmlrpcstr['no_data'] = 'No data received from server.';

        $this->initialize($config);
    }

    // --------------------------------------------------------------------

    /**
     * Initialize
     *
     * @param array $config
     * @return void
     */
    public function initialize($config = [])
    {
        if (count($config) > 0) {
            foreach ($config as $key => $val) {
                if (isset($this->$key)) {
                    $this->$key = $val;
                }
            }
        }
    }

    // --------------------------------------------------------------------

    /**
     * Parse server URL
     *
     * @param string $url
     * @param int $port
     * @param bool $proxy
     * @param int $proxy_port
     * @return void
     */
    public function server($url, $port = 80, $proxy = false, $proxy_port = 8080)
    {
        if (stripos($url, 'http') !== 0) {
            $url = 'http://'.$url;
        }

        $parts = parse_url($url);

        if (isset($parts['user'], $parts['pass'])) {
            $parts['host'] = $parts['user'].':'.$parts['pass'].'@'.$parts['host'];
        }

        $path = $parts['path'] ?? '/';

        if (!empty($parts['query'])) {
            $path .= '?'.$parts['query'];
        }

        // Create new client with dependency injection
        $this->client = new XmlRpcClient(
            TransportFactory::create(),
            EncoderFactory::create(),
            DecoderFactory::create()
        );

        $this->client->setServer($url, $port);

        if ($this->debug) {
            $this->client->setDebug(true);
        }
    }

    // --------------------------------------------------------------------

    /**
     * Set Timeout
     *
     * @param int $seconds
     * @return void
     */
    public function timeout($seconds = 5)
    {
        if ($this->client instanceof XmlRpcClient && is_int($seconds)) {
            $this->client->setTimeout($seconds);
        }
    }

    // --------------------------------------------------------------------

    /**
     * Set Methods
     *
     * @param string $function Method name
     * @return void
     */
    public function method($function)
    {
        $this->method = $function;
    }

    // --------------------------------------------------------------------

    /**
     * Take Array of Data and Create Objects
     *
     * @param array $incoming
     * @return void
     */
    public function request($incoming)
    {
        if (!is_array($incoming)) {
            return;
        }

        $this->data = [];

        foreach ($incoming as $key => $value) {
            $this->data[$key] = $this->values_parsing($value);
        }
    }

    // --------------------------------------------------------------------

    /**
     * Values Parsing
     *
     * @param mixed $value
     * @return XmlRpcValue
     */
    public function values_parsing($value)
    {
        if (is_array($value) && array_key_exists(0, $value)) {
            if (!isset($value[1], $this->xmlrpcTypes[$value[1]])) {
                $temp = new XmlRpcValue($value[0], is_array($value[0]) ? 'array' : 'string');
            } else {
                if (is_array($value[0]) && ($value[1] === 'struct' || $value[1] === 'array')) {
                    foreach (array_keys($value[0]) as $k) {
                        $value[0][$k] = $this->values_parsing($value[0][$k]);
                    }
                }

                $temp = new XmlRpcValue($value[0], $value[1]);
            }
        } else {
            $temp = new XmlRpcValue($value, 'string');
        }

        return $temp;
    }

    // --------------------------------------------------------------------

    /**
     * Set Debug
     *
     * @param bool $flag
     * @return void
     */
    public function set_debug($flag = true)
    {
        $this->debug = ($flag === true);

        if ($this->client instanceof XmlRpcClient) {
            $this->client->setDebug($this->debug);
        }
    }

    // --------------------------------------------------------------------

    /**
     * Sends XML-RPC Request
     *
     * @return bool
     */
    public function send_request()
    {
        try {
            $this->message = new XmlRpcMessage($this->method, $this->data);
            $this->message->debug = $this->debug;

            $result = $this->client->call($this->method, $this->data);

            if ($result === null) {
                $this->error = 'Empty response from server';
                return false;
            }

            $this->response = $result;
            return true;
        } catch (FaultException $e) {
            $this->error = $e->getFaultString();
            return false;
        } catch (TransportException $e) {
            $this->error = $e->getMessage();
            return false;
        } catch (XmlRpcException $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    // --------------------------------------------------------------------

    /**
     * Returns Error
     *
     * @return string
     */
    public function display_error()
    {
        return $this->error;
    }

    // --------------------------------------------------------------------

    /**
     * Returns Remote Server Response
     *
     * @return array
     */
    public function display_response()
    {
        return $this->response;
    }

    // --------------------------------------------------------------------

    /**
     * Sends an Error Message for Server Request
     *
     * @param int $number
     * @param string $message
     * @return XmlRpcResponse
     */
    public function send_error_message($number, $message)
    {
        return new XmlRpcResponse(0, $number, $message);
    }

    // --------------------------------------------------------------------

    /**
     * Send Response for Server Request
     *
     * @param array $response
     * @return XmlRpcResponse
     */
    public function send_response($response)
    {
        return new XmlRpcResponse($this->values_parsing($response));
    }
}
