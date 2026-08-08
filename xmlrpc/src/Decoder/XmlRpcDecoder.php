<?php

declare(strict_types=0);

namespace Kodhe\Framework\Xmlrpc\Decoder;

use Kodhe\Framework\Xmlrpc\Contracts\DecoderInterface;
use Kodhe\Framework\Xmlrpc\Exceptions\XmlParseException;
use Kodhe\Framework\Xmlrpc\Support\XmlParserCache;

/**
 * XML-RPC decoder implementation using Strategy pattern
 */
class XmlRpcDecoder implements DecoderInterface
{
    /**
     * @var array
     */
    private $validParents = [
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

    /**
     * @var array
     */
    private $xh = [];

    /**
     * Decode XML-RPC response to PHP value
     *
     * @param string $xml
     * @return mixed
     * @throws XmlParseException
     */
    public function decode(string $xml)
    {
        $parser = XmlParserCache::getParser('UTF-8');
        XmlParserCache::resetParser($parser);

        $pname = (string) $parser;
        $this->xh[$pname] = [
            'isf' => 0,
            'ac' => '',
            'headers' => [],
            'stack' => [],
            'valuestack' => [],
            'isf_reason' => '',
            'value' => null,
            'vt' => 'value',
            'lv' => 0,
            'params' => [],
            'method' => '',
        ];

        xml_set_object($parser, $this);
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, true);
        xml_set_element_handler($parser, [$this, 'openTag'], [$this, 'closingTag']);
        xml_set_character_data_handler($parser, [$this, 'characterData']);

        // Parse headers and data
        $lines = explode("\r\n", $xml);
        while (($line = array_shift($lines))) {
            if (strlen($line) < 1) {
                break;
            }
            $this->xh[$pname]['headers'][] = $line;
        }
        $data = implode("\r\n", $lines);

        if (!xml_parse($parser, $data, true)) {
            $errstr = sprintf(
                'XML error: %s at line %d',
                xml_error_string(xml_get_error_code($parser)),
                xml_get_current_line_number($parser)
            );
            throw new XmlParseException($errstr, xml_get_error_code($parser));
        }

        XmlParserCache::resetParser($parser);

        if ($this->xh[$pname]['isf'] > 1) {
            throw new XmlParseException($this->xh[$pname]['isf_reason'], 0, null, $this->xh[$pname]['isf']);
        }

        return $this->xh[$pname];
    }

    /**
     * Open tag handler
     *
     * @param resource $parser
     * @param string $name
     * @return void
     */
    public function openTag($parser, string $name): void
    {
        $pname = (string) $parser;

        if ($this->xh[$pname]['isf'] > 1) {
            return;
        }

        if (count($this->xh[$pname]['stack']) === 0) {
            if ($name !== 'METHODRESPONSE' && $name !== 'METHODCALL') {
                $this->xh[$pname]['isf'] = 2;
                $this->xh[$pname]['isf_reason'] = 'Top level XML-RPC element is missing';
                return;
            }
        } elseif (!in_array($this->xh[$pname]['stack'][0], $this->validParents[$name] ?? [], true)) {
            $this->xh[$pname]['isf'] = 2;
            $this->xh[$pname]['isf_reason'] = "XML-RPC element {$name} cannot be child of ".$this->xh[$pname]['stack'][0];
            return;
        }

        switch ($name) {
            case 'STRUCT':
            case 'ARRAY':
                $curVal = ['value' => [], 'type' => $name];
                array_unshift($this->xh[$pname]['valuestack'], $curVal);
                break;
            case 'METHODNAME':
            case 'NAME':
                $this->xh[$pname]['ac'] = '';
                break;
            case 'FAULT':
                $this->xh[$pname]['isf'] = 1;
                break;
            case 'PARAM':
                $this->xh[$pname]['value'] = null;
                break;
            case 'VALUE':
                $this->xh[$pname]['vt'] = 'value';
                $this->xh[$pname]['ac'] = '';
                $this->xh[$pname]['lv'] = 1;
                break;
            case 'I4':
            case 'INT':
            case 'STRING':
            case 'BOOLEAN':
            case 'DOUBLE':
            case 'DATETIME.ISO8601':
            case 'BASE64':
                if ($this->xh[$pname]['vt'] !== 'value') {
                    $this->xh[$pname]['isf'] = 2;
                    $this->xh[$pname]['isf_reason'] = 'There is a '.$name.' element following a '
                        .$this->xh[$pname]['vt'].' element inside a single value';
                    return;
                }
                $this->xh[$pname]['ac'] = '';
                break;
            case 'MEMBER':
                $this->xh[$pname]['valuestack'][0]['name'] = '';
                $this->xh[$pname]['value'] = null;
                break;
            case 'DATA':
            case 'METHODCALL':
            case 'METHODRESPONSE':
            case 'PARAMS':
                break;
            default:
                $this->xh[$pname]['isf'] = 2;
                $this->xh[$pname]['isf_reason'] = "Invalid XML-RPC element found: {$name}";
                break;
        }

        array_unshift($this->xh[$pname]['stack'], $name);

        if ($name !== 'VALUE') {
            $this->xh[$pname]['lv'] = 0;
        }
    }

    /**
     * Close tag handler
     *
     * @param resource $parser
     * @param string $name
     * @return void
     */
    public function closingTag($parser, string $name): void
    {
        $pname = (string) $parser;

        if ($this->xh[$pname]['isf'] > 1) {
            return;
        }

        $currElem = array_shift($this->xh[$pname]['stack']);

        switch ($name) {
            case 'STRUCT':
            case 'ARRAY':
                $curVal = array_shift($this->xh[$pname]['valuestack']);
                $this->xh[$pname]['value'] = $curVal['values'] ?? [];
                $this->xh[$pname]['vt'] = strtolower($name);
                break;
            case 'NAME':
                $this->xh[$pname]['valuestack'][0]['name'] = $this->xh[$pname]['ac'];
                break;
            case 'BOOLEAN':
            case 'I4':
            case 'INT':
            case 'STRING':
            case 'DOUBLE':
            case 'DATETIME.ISO8601':
            case 'BASE64':
                $this->xh[$pname]['vt'] = strtolower($name);
                if ($name === 'STRING') {
                    $this->xh[$pname]['value'] = $this->xh[$pname]['ac'];
                } elseif ($name === 'DATETIME.ISO8601') {
                    $this->xh[$pname]['vt'] = 'dateTime.iso8601';
                    $this->xh[$pname]['value'] = $this->xh[$pname]['ac'];
                } elseif ($name === 'BASE64') {
                    $this->xh[$pname]['value'] = base64_decode($this->xh[$pname]['ac']);
                } elseif ($name === 'BOOLEAN') {
                    $this->xh[$pname]['value'] = (bool) $this->xh[$pname]['ac'];
                } elseif ($name === 'DOUBLE') {
                    $this->xh[$pname]['value'] = preg_match('/^[+-]?[eE0-9\t \.]+$/', $this->xh[$pname]['ac'])
                        ? (float) $this->xh[$pname]['ac']
                        : 'ERROR_NON_NUMERIC_FOUND';
                } else {
                    $this->xh[$pname]['value'] = preg_match('/^[+-]?[0-9\t ]+$/', $this->xh[$pname]['ac'])
                        ? (int) $this->xh[$pname]['ac']
                        : 'ERROR_NON_NUMERIC_FOUND';
                }
                $this->xh[$pname]['ac'] = '';
                $this->xh[$pname]['lv'] = 3;
                break;
            case 'VALUE':
                if ($this->xh[$pname]['vt'] === 'value') {
                    $this->xh[$pname]['value'] = $this->xh[$pname]['ac'];
                    $this->xh[$pname]['vt'] = 'string';
                }
                if (count($this->xh[$pname]['valuestack']) && $this->xh[$pname]['valuestack'][0]['type'] === 'ARRAY') {
                    $this->xh[$pname]['valuestack'][0]['values'][] = $this->xh[$pname]['value'];
                } else {
                    $this->xh[$pname]['value'] = $this->xh[$pname]['value'];
                }
                break;
            case 'MEMBER':
                $this->xh[$pname]['ac'] = '';
                if ($this->xh[$pname]['value']) {
                    $this->xh[$pname]['valuestack'][0]['values'][$this->xh[$pname]['valuestack'][0]['name']] = $this->xh[$pname]['value'];
                }
                break;
            case 'DATA':
                $this->xh[$pname]['ac'] = '';
                break;
            case 'PARAM':
                if ($this->xh[$pname]['value']) {
                    $this->xh[$pname]['params'][] = $this->xh[$pname]['value'];
                }
                break;
            case 'METHODNAME':
                $this->xh[$pname]['method'] = ltrim($this->xh[$pname]['ac']);
                break;
            case 'PARAMS':
            case 'FAULT':
            case 'METHODCALL':
            case 'METHORESPONSE':
                break;
        }
    }

    /**
     * Character data handler
     *
     * @param resource $parser
     * @param string $data
     * @return void
     */
    public function characterData($parser, string $data): void
    {
        $pname = (string) $parser;

        if ($this->xh[$pname]['isf'] > 1) {
            return;
        }

        if ($this->xh[$pname]['lv'] !== 3) {
            if ($this->xh[$pname]['lv'] === 1) {
                $this->xh[$pname]['lv'] = 2;
            }

            if (!isset($this->xh[$pname]['ac'])) {
                $this->xh[$pname]['ac'] = '';
            }

            $this->xh[$pname]['ac'] .= $data;
        }
    }

    /**
     * Convert decoded XML-RPC value to PHP type
     *
     * @param mixed $xmlrpcVal
     * @return mixed
     */
    public function xmlRpcDecoder($xmlrpcVal)
    {
        if (is_object($xmlrpcVal) && method_exists($xmlrpcVal, 'kindOf')) {
            $kind = $xmlrpcVal->kindOf();

            if ($kind === 'scalar') {
                return $xmlrpcVal->scalarval();
            } elseif ($kind === 'array') {
                reset($xmlrpcVal->me);
                $b = current($xmlrpcVal->me);
                $arr = [];
                for ($i = 0, $c = count($b); $i < $c; $i++) {
                    $arr[] = $this->xmlRpcDecoder($xmlrpcVal->me['array'][$i]);
                }
                return $arr;
            } elseif ($kind === 'struct') {
                reset($xmlrpcVal->me['struct']);
                $arr = [];
                foreach ($xmlrpcVal->me['struct'] as $key => &$value) {
                    $arr[$key] = $this->xmlRpcDecoder($value);
                }
                return $arr;
            }
        }

        return $xmlrpcVal;
    }
}
