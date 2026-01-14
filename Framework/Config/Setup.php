<?php

return [
    'author' => 'Your Name',
    'author_url' => 'https://example.com',
    'name' => 'Your Application',
    'description' => 'Your application description',
    'version' => '1.0.0',
    'namespace' => 'Kodhe',
    'aliases' => [
        'Service' => Kodhe\Framework\Container\ServiceHelper::class,
        'Route' => Kodhe\Framework\Routing\Route::class,
        'Request' => Kodhe\Framework\Http\Request::class,
        'Response' => Kodhe\Framework\Http\Response::class,
        'DB' => Kodhe\Framework\Database\Connection\ConnectionManager::class,
        'Model' => Kodhe\Framework\Database\Model::class,
        'Language' => Kodhe\Framework\Support\Language::class,
        'Router' => Kodhe\Framework\Routing\Router::class,
        'Config' => Kodhe\Framework\Config\Config::class,
        'Loader' => Kodhe\Framework\Config\Loaders\FileLoader::class,
        'Controller' => Kodhe\Framework\Http\Controllers\BaseController::class,
        'Hooks' => Kodhe\Framework\Support\Legacy\Hooks::class,
        'Input' => Kodhe\Framework\Support\Legacy\Input::class,
        'URI' => Kodhe\Framework\Support\Legacy\URI::class,
        'Output' => Kodhe\Framework\Support\Legacy\Output::class,
        'Utf8' => Kodhe\Framework\Support\Legacy\Utf8::class,
        'Security' => Kodhe\Framework\Support\Legacy\Security::class,
        'Benchmark' => Kodhe\Framework\Support\Legacy\Benchmark::class,

        // Codeigniter 3 Alias
        'CI_Model' => Kodhe\Framework\Database\ORM\Model::class,
        'CI_Lang' => Language::class,
        'CI_Router' => Router::class,
        'CI_Config' => Config::class,
        'CI_Loader' => Loader::class,
        'CI_Controller' => Controller::class,
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
            return new \Kodhe\Library\Cache\Cache();
        }, 
        'calendar' => function ($provider) {
            return new \Kodhe\Library\Calendar\Calendar();
        }, 
        'cart' => function ($provider) {
            return new \Kodhe\Library\Cart\Cart();
        }, 
        'driver' => function ($provider) {
            return new \Kodhe\Library\Driver\Driver();
        }, 
        'email' => function ($provider) {
            return new \Kodhe\Library\Email\Email();
        }, 
        'encrypt' => function ($provider) {
            return new \Kodhe\Library\Encrypt\Encrypt();
        }, 
        'encryption' => function ($provider) {
            return new \Kodhe\Library\Encryption\Encryption();
        }, 
        'form_validation' => function ($provider) {
            return new \Kodhe\Library\FormValidation\FormValidation();
        }, 
        'ftp' => function ($provider) {
            return new \Kodhe\Library\Ftp\Ftp();
        }, 
        'image_lib' => function ($provider) {
            return new \Kodhe\Library\ImageLib\ImageLib();
        }, 
        'javascript' => function ($provider) {
            return new \Kodhe\Library\Javascript\Javascript();
        }, 
        'migration' => function ($provider) {
            return new \Kodhe\Library\Migration\Migration();
        }, 
        'pagination' => function ($provider) {
            return new \Kodhe\Library\Pagination\Pagination();
        }, 
        'parser' => function ($provider) {
            return new \Kodhe\Library\Parser\Parser();
        }, 
        'profiler' => function ($provider) {
            return new \Kodhe\Library\Profiler\Profiler();
        }, 
        'table' => function ($provider) {
            return new \Kodhe\Library\Table\Table();
        }, 
        'trackback' => function ($provider) {
            return new \Kodhe\Library\Trackback\Trackback();
        }, 
        'typography' => function ($provider) {
            return new \Kodhe\Library\Typography\Typography();
        }, 
        'unit_test' => function ($provider) {
            return new \Kodhe\Library\UnitTest\UnitTest();
        }, 

        'upload' => function ($provider) {
            return new \Kodhe\Library\Upload\Upload();
        }, 

        'agent' => function ($provider) {
            return new \Kodhe\Library\UserAgent\UserAgent();
        }, 

        'xmlrpc' => function ($provider) {
            return new \Kodhe\Library\Xmlrpc\Xmlrpc();
        }, 

        'xmlrpcs' => function ($provider) {
            return new \Kodhe\Library\Xmlrpcs\Xmlrpcs();
        }, 

        'zip' => function ($provider) {
            return new \Kodhe\Library\Zip\Zip();
        }, 
        'session' => function ($provider) {
            return new \Kodhe\Library\Session\Session();
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
            return new \Kodhe\Framework\View\ViewFactory();
        },   
        'load' => function ($provider) {
            return new Loader();
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