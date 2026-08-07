<?php

declare(strict_types=1);

namespace Kodhe\Xmlrpcs;

use Kodhe\Xmlrpcs\Contracts\MethodRegistryInterface;
use Kodhe\Xmlrpcs\Contracts\DispatcherInterface;
use Kodhe\Xmlrpcs\Registry\MethodRegistry;
use Kodhe\Xmlrpcs\Dispatcher\RequestDispatcher;
use Kodhe\Xmlrpcs\ValueObjects\Request;
use Kodhe\Xmlrpcs\Exceptions\UnknownMethodException;

if (!function_exists('xml_parser_create')) {
    show_error('Your PHP installation does not support XML');
}

if (!class_exists('Kodhe\Xmlrpc\Xmlrpc', false)) {
    show_error('You must load the Xmlrpc class before loading the Xmlrpcs class in order to create a server.');
}

// ------------------------------------------------------------------------

/**
 * XML-RPC server class - Refactored with modular architecture
 * Maintains 100% backward compatibility with CI3 API
 *
 * @package     Kodhe\Xmlrpcs
 * @category    XML-RPC
 */
class Xmlrpcs extends \Kodhe\Xmlrpc\Xmlrpc
{
    /**
     * Method registry
     *
     * @var MethodRegistryInterface
     */
    protected MethodRegistryInterface $registry;

    /**
     * Request dispatcher (lazy initialized)
     *
     * @var DispatcherInterface|null
     */
    protected ?DispatcherInterface $dispatcher = null;

    /**
     * Configuration object
     *
     * @var object|null
     */
    public $object = false;

    /**
     * Debug message
     *
     * @var string
     */
    public $debug_msg = '';

    /**
     * Cache for registered method names
     *
     * @var array|null
     */
    protected ?array $methodNamesCache = null;

    /**
     * Constructor
     *
     * @param array $config
     */
    public function __construct($config = [])
    {
        parent::__construct();
        
        // Initialize registry
        $this->registry = new MethodRegistry();
        
        // Set up system methods
        $this->set_system_methods();

        if (isset($config['functions']) && is_array($config['functions'])) {
            $this->methods = array_merge($this->methods, $config['functions']);
            $this->registry->merge($config['functions']);
        }

        log_message('info', 'XML-RPC Server Class Initialized');
    }

    // --------------------------------------------------------------------

    /**
     * Initialize Prefs and Serve
     *
     * @param mixed $config
     * @return void
     */
    public function initialize($config = [])
    {
        if (isset($config['functions']) && is_array($config['functions'])) {
            $this->methods = array_merge($this->methods, $config['functions']);
            $this->registry->merge($config['functions']);
            $this->methodNamesCache = null;
        }

        if (isset($config['debug'])) {
            $this->debug = $config['debug'];
        }

        if (isset($config['object']) && is_object($config['object'])) {
            $this->object = $config['object'];
            
            // Update dispatcher context if already created
            if ($this->dispatcher !== null) {
                $this->dispatcher->setObjectContext($this->object);
            }
        }

        if (isset($config['xss_clean'])) {
            $this->xss_clean = $config['xss_clean'];
        }
    }

    // --------------------------------------------------------------------

    /**
     * Setting of System Methods
     *
     * @return void
     */
    public function set_system_methods()
    {
        $systemMethods = [
            'system.listMethods' => [
                'function' => 'this.listMethods',
                'signature' => [[$this->xmlrpcArray, $this->xmlrpcString], [$this->xmlrpcArray]],
                'docstring' => 'Returns an array of available methods on this server'
            ],
            'system.methodHelp' => [
                'function' => 'this.methodHelp',
                'signature' => [[$this->xmlrpcString, $this->xmlrpcString]],
                'docstring' => 'Returns a documentation string for the specified method'
            ],
            'system.methodSignature' => [
                'function' => 'this.methodSignature',
                'signature' => [[$this->xmlrpcArray, $this->xmlrpcString]],
                'docstring' => 'Returns an array describing the return type and required parameters of a method'
            ],
            'system.multicall' => [
                'function' => 'this.multicall',
                'signature' => [[$this->xmlrpcArray, $this->xmlrpcArray]],
                'docstring' => 'Combine multiple RPC calls in one request'
            ]
        ];

        $this->methods = array_merge($this->methods, $systemMethods);
        $this->registry->merge($systemMethods);
        $this->methodNamesCache = null;
    }

    // --------------------------------------------------------------------

    /**
     * Main Server Function
     *
     * @return void
     */
    public function serve()
    {
        $r = $this->parseRequest();
        $payload = '<?xml version="1.0" encoding="'.$this->xmlrpc_defencoding.'"'.'>'."\n".$this->debug_msg.$r->prepare_response();

        header('Content-Type: text/xml');
        header('Content-Length: '.strlen($payload));
        exit($payload);
    }

    // --------------------------------------------------------------------

    /**
     * Add Method to Class (backward compatible)
     *
     * @param string $methodname
     * @param string $function
     * @param array $sig
     * @param string $doc
     * @return void
     */
    public function add_to_map($methodname, $function, $sig, $doc)
    {
        $this->methods[$methodname] = [
            'function' => $function,
            'signature' => $sig,
            'docstring' => $doc
        ];
        
        $this->registry->register($methodname, $this->methods[$methodname]);
        $this->methodNamesCache = null;
    }

    // --------------------------------------------------------------------

    /**
     * Set methods (new API)
     *
     * @param array $methods
     * @return void
     */
    public function set_methods(array $methods): void
    {
        foreach ($methods as $name => $definition) {
            $this->methods[$name] = $definition;
        }
        $this->registry->merge($methods);
        $this->methodNamesCache = null;
    }

    // --------------------------------------------------------------------

    /**
     * Verify request (authentication hook)
     *
     * @param string $data
     * @return bool
     */
    public function verify_request(string $data): bool
    {
        // Override this method in subclasses for authentication
        return true;
    }

    // --------------------------------------------------------------------

    /**
     * Parse Server Request
     *
     * @param string $data
     * @return \XML_RPC_Response
     */
    public function parseRequest($data = '')
    {
        if ($data === '') {
            if (kodhe()->input->method() === 'post') {
                $data = kodhe()->input->raw_input_stream;
            }
        }

        if (!$this->verify_request($data)) {
            return new \XML_RPC_Response(0, $this->xmlrpcerr['invalid_return'], 'Request verification failed');
        }

        $parser = xml_parser_create($this->xmlrpc_defencoding);
        $parserObject = new \XML_RPC_Message('filler');
        $pname = (string) $parser;

        $parserObject->xh[$pname] = [
            'isf' => 0,
            'isf_reason' => '',
            'params' => [],
            'stack' => [],
            'valuestack' => [],
            'method' => ''
        ];

        xml_set_object($parser, $parserObject);
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, true);
        xml_set_element_handler($parser, 'open_tag', 'closing_tag');
        xml_set_character_data_handler($parser, 'character_data');

        $plist = '';

        if (!xml_parse($parser, $data, 1)) {
            $errorCode = xml_get_error_code($parser);
            xml_parser_free($parser);
            
            return new \XML_RPC_Response(
                0,
                $this->xmlrpcerrxml + $errorCode,
                sprintf('XML error: %s at line %d', xml_error_string($errorCode), xml_get_current_line_number($parser))
            );
        }

        xml_parser_free($parser);

        if ($parserObject->xh[$pname]['isf']) {
            return new \XML_RPC_Response(0, $this->xmlrpcerr['invalid_return'], $this->xmlrpcstr['invalid_return']);
        }

        if ($this->debug === true) {
            for ($i = 0, $c = count($parserObject->xh[$pname]['params']); $i < $c; $i++) {
                $plist .= $i.' - '.print_r(get_object_vars($parserObject->xh[$pname]['params'][$i]), true).";\n";
            }
        }

        $m = new \XML_RPC_Message($parserObject->xh[$pname]['method']);
        for ($i = 0, $c = count($parserObject->xh[$pname]['params']); $i < $c; $i++) {
            $m->addParam($parserObject->xh[$pname]['params'][$i]);
        }

        if ($this->debug === true) {
            $this->debug_msg = "<!-- DEBUG INFO:\n\n{$plist}\n END DEBUG-->\n";
        }

        return $this->_execute($m);
    }

    // --------------------------------------------------------------------

    /**
     * Get or create dispatcher (lazy initialization)
     *
     * @return DispatcherInterface
     */
    protected function getDispatcher(): DispatcherInterface
    {
        if ($this->dispatcher === null) {
            $this->dispatcher = new RequestDispatcher($this->registry);
            $this->dispatcher->setDebug($this->debug);
        }
        
        return $this->dispatcher;
    }

    // --------------------------------------------------------------------

    /**
     * Executes the Method
     *
     * @param object $m
     * @return \XML_RPC_Response
     */
    protected function _execute($m)
    {
        $methName = $m->method_name;
        $system_call = (strpos($methName, 'system') === 0);

        if ($this->xss_clean === false) {
            $m->xss_clean = false;
        }

        if (!isset($this->methods[$methName]['function'])) {
            return new \XML_RPC_Response(0, $this->xmlrpcerr['unknown_method'], $this->xmlrpcstr['unknown_method']);
        }

        $method_parts = explode('.', $this->methods[$methName]['function']);
        $objectCall = !empty($method_parts[1]);

        if ($system_call === true) {
            if (!is_callable([$this, $method_parts[1]])) {
                return new \XML_RPC_Response(0, $this->xmlrpcerr['unknown_method'], $this->xmlrpcstr['unknown_method']);
            }
        } elseif (($objectCall && !is_callable([$method_parts[0], $method_parts[1]]))
            || (!$objectCall && !is_callable($this->methods[$methName]['function']))) {
            return new \XML_RPC_Response(0, $this->xmlrpcerr['unknown_method'], $this->xmlrpcstr['unknown_method']);
        }

        if (isset($this->methods[$methName]['signature'])) {
            $sig = $this->methods[$methName]['signature'];
            for ($i = 0, $c = count($sig); $i < $c; $i++) {
                $current_sig = $sig[$i];

                if (count($current_sig) === count($m->params) + 1) {
                    for ($n = 0, $mc = count($m->params); $n < $mc; $n++) {
                        $p = $m->params[$n];
                        $pt = ($p->kindOf() === 'scalar') ? $p->scalarval() : $p->kindOf();

                        if ($pt !== $current_sig[$n + 1]) {
                            return new \XML_RPC_Response(
                                0,
                                $this->xmlrpcerr['incorrect_params'],
                                $this->xmlrpcstr['incorrect_params'] . ': Wanted '.$current_sig[$n + 1].', got '.$pt.' at param '.($n + 1).')'
                            );
                        }
                    }
                }
            }
        }

        if ($objectCall === true) {
            if ($method_parts[0] === 'this' && $system_call === true) {
                return call_user_func([$this, $method_parts[1]], $m);
            } elseif ($this->object === false) {
                return kodhe()->{$method_parts[1]}($m);
            }

            return $this->object->{$method_parts[1]}($m);
        }

        return call_user_func($this->methods[$methName]['function'], $m);
    }

    // --------------------------------------------------------------------

    /**
     * Server Function: List Methods
     *
     * @param mixed $m
     * @return \XML_RPC_Response
     */
    public function listMethods($m)
    {
        $v = new \XML_RPC_Values();
        $output = [];

        foreach ($this->methods as $key => $value) {
            $output[] = new \XML_RPC_Values($key, 'string');
        }

        $v->addArray($output);
        return new \XML_RPC_Response($v);
    }

    // --------------------------------------------------------------------

    /**
     * Server Function: Return Signature for Method
     *
     * @param mixed $m
     * @return \XML_RPC_Response
     */
    public function methodSignature($m)
    {
        $parameters = $m->output_parameters();
        $method_name = $parameters[0];

        if (isset($this->methods[$method_name])) {
            if ($this->methods[$method_name]['signature']) {
                $sigs = [];
                $signature = $this->methods[$method_name]['signature'];

                for ($i = 0, $c = count($signature); $i < $c; $i++) {
                    $cursig = [];
                    $inSig = $signature[$i];
                    for ($j = 0, $jc = count($inSig); $j < $jc; $j++) {
                        $cursig[] = new \XML_RPC_Values($inSig[$j], 'string');
                    }
                    $sigs[] = new \XML_RPC_Values($cursig, 'array');
                }

                return new \XML_RPC_Response(new \XML_RPC_Values($sigs, 'array'));
            }

            return new \XML_RPC_Response(new \XML_RPC_Values('undef', 'string'));
        }

        return new \XML_RPC_Response(0, $this->xmlrpcerr['introspect_unknown'], $this->xmlrpcstr['introspect_unknown']);
    }

    // --------------------------------------------------------------------

    /**
     * Server Function: Doc String for Method
     *
     * @param mixed $m
     * @return \XML_RPC_Response
     */
    public function methodHelp($m)
    {
        $parameters = $m->output_parameters();
        $method_name = $parameters[0];

        if (isset($this->methods[$method_name])) {
            $docstring = isset($this->methods[$method_name]['docstring']) 
                ? $this->methods[$method_name]['docstring'] 
                : '';

            return new \XML_RPC_Response(new \XML_RPC_Values($docstring, 'string'));
        }

        return new \XML_RPC_Response(0, $this->xmlrpcerr['introspect_unknown'], $this->xmlrpcstr['introspect_unknown']);
    }

    // --------------------------------------------------------------------

    /**
     * Server Function: Multi-call
     *
     * @param mixed $m
     * @return \XML_RPC_Response
     */
    public function multicall($m)
    {
        return new \XML_RPC_Response(0, $this->xmlrpcerr['unknown_method'], $this->xmlrpcstr['unknown_method']);
    }
}
