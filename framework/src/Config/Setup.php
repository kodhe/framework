<?php

namespace Kodhe\Framework\Config;

use Kodhe\Framework\Container\ServiceHelper;
use Kodhe\Http\Routing\Route;
use Kodhe\Http\Request;
use Kodhe\Http\Response;
use Kodhe\Database\Connection\ConnectionManager;
use Kodhe\Database\Model;
use Kodhe\Framework\Support\Language;
use Kodhe\Http\Routing\Router;
use Kodhe\Framework\Config\Config;
use Kodhe\Framework\Config\Loaders\FileLoader;
use Kodhe\Http\Controllers\BaseController;
use Kodhe\Framework\Support\Legacy\Hooks;
use Kodhe\Framework\Support\Legacy\Input;
use Kodhe\Framework\Support\Legacy\URI;
use Kodhe\Framework\Support\Legacy\Output;
use Kodhe\Framework\Support\Legacy\Utf8;
use Kodhe\Framework\Support\Legacy\Security;
use Kodhe\Framework\Support\Legacy\Benchmark;
use Kodhe\Framework\Database\ORM\CI_Model;
use Kodhe\Framework\View\ViewFactory;

return [
    'author' => 'Your Name',
    'author_url' => 'https://example.com',
    'name' => 'Your Application',
    'description' => 'Your application description',
    'version' => '1.0.0',
    'namespace' => 'Kodhe',
    'aliases' => [
        'Service' => ServiceHelper::class,
        'Route' => Route::class,
        'Request' => Request::class,
        'Response' => Response::class,
        'DB' => ConnectionManager::class,
        'Model' => Model::class,
        'Language' => Language::class,
        'Router' => Router::class,
        'Config' => Config::class,
        'Loader' => FileLoader::class,
        'Controller' => BaseController::class,
        'Hooks' => Hooks::class,
        'Input' => Input::class,
        'URI' => URI::class,
        'Output' => Output::class,
        'Utf8' => Utf8::class,
        'Security' => Security::class,
        'Benchmark' => Benchmark::class,

        // Codeigniter 3 Alias
        'CI_Model' => CI_Model::class,
        'CI_model' => CI_Model::class,
        'CI_Lang' => Language::class,
        'CI_Router' => Router::class,
        'CI_Config' => Config::class,
        'CI_Loader' => FileLoader::class,
        'CI_Controller' => BaseController::class,
        'CI_Hooks' => Hooks::class,
        'CI_Input' => Input::class,
        'CI_URI' => URI::class,
        'CI_Output' => Output::class,
        'CI_Utf8' => Utf8::class,
        'CI_Security' => Security::class,
        'CI_Benchmark' => Benchmark::class,
    ],
    'services' => [
        'cache' => function ($provider) {
            return new \Kodhe\Cache\Cache();
        }, 
        'calendar' => function ($provider) {
            return new \Kodhe\Calendar\Calendar();
        }, 
        'cart' => function ($provider) {
            return new \Kodhe\Cart\Cart();
        }, 
        'driver' => function ($provider) {
            return new \Kodhe\Driver\Driver();
        }, 
        'email' => function ($provider) {
            return new \Kodhe\Email\Email();
        }, 
        'encrypt' => function ($provider) {
            return new \Kodhe\Encrypt\Encrypt();
        }, 
        'encryption' => function ($provider) {
            return new \Kodhe\Encryption\Encryption();
        }, 
        'form_validation' => function ($provider) {
            return new \Kodhe\FormValidation\FormValidation();
        }, 
        'ftp' => function ($provider) {
            return new \Kodhe\Ftp\Ftp();
        }, 
        'image_lib' => function ($provider) {
            return new \Kodhe\ImageLib\ImageLib();
        }, 
        'javascript' => function ($provider) {
            return new \Kodhe\Javascript\Javascript();
        }, 
        'migration' => function ($provider) {
            return new \Kodhe\Migration\Migration();
        }, 
        'pagination' => function ($provider) {
            return new \Kodhe\Pagination\Pagination();
        }, 
        'parser' => function ($provider) {
            return new \Kodhe\Parser\Parser();
        }, 
        'profiler' => function ($provider) {
            return new \Kodhe\Profiler\Profiler();
        }, 
        'table' => function ($provider) {
            return new \Kodhe\Table\Table();
        }, 
        'trackback' => function ($provider) {
            return new \Kodhe\Trackback\Trackback();
        }, 
        'typography' => function ($provider) {
            return new \Kodhe\Typography\Typography();
        }, 
        'unit_test' => function ($provider) {
            return new \Kodhe\UnitTest\UnitTest();
        }, 

        'upload' => function ($provider) {
            return new \Kodhe\Upload\Upload();
        }, 

        'agent' => function ($provider) {
            return new \Kodhe\UserAgent\UserAgent();
        }, 

        'xmlrpc' => function ($provider) {
            return new \Kodhe\Xmlrpc\Xmlrpc();
        }, 

        'xmlrpcs' => function ($provider) {
            return new \Kodhe\Xmlrpcs\Xmlrpcs();
        }, 

        'zip' => function ($provider) {
            return new \Kodhe\Zip\Zip();
        }, 
        'session' => function ($provider) {
            return new \Kodhe\Session\Session();
        },
    ],
    'services.singletons' => [
        'benchmark' => function ($provider) {
            return new Benchmark();
        },       
        'input' => function ($provider) {
            return new Input();
        },
        'hooks' => function ($provider) {
            return new Hooks();
        },
        'lang' => function ($provider) {
            return new Language();
        },
        'config' => function ($provider) {
            return new Config();
        },
        'router' => function ($provider) {
            return new Router();
        },
        'uri' => function ($provider) {
            return new URI();
        },
        'output' => function ($provider) {
            return new Output();
        },
        'utf8' => function ($provider) {
            return new Utf8();
        },
        'security' => function ($provider) {
            return new Security();
        },
        'view' => function ($provider) {
            if (!class_exists(ViewFactory::class)) {
                // Package kodhe/view not installed — return null so boot can continue.
                // Controllers that need views should install: composer require kodhe/view
                if (function_exists('log_message')) {
                    log_message('error', 'ViewFactory not found. Install kodhe/view package.');
                }
                return null;
            }
            return new ViewFactory();
        },   
        'load' => function ($provider) {
            return new FileLoader();
        },   

    ],
    'models' => [
        // Register your models here
        // 'Example' => 'Model\\Example'
    ],
    
    'models.dependencies' => [
        // Model dependencies
    ],
    
    'cookies' => [
        'necessary' => [],
        'functionality' => [],
        'performance' => [],
        'targeting' => []
    ],
  
];