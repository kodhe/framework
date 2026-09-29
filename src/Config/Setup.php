<?php

return [
    'author' => 'Kodhe',
    'author_url' => 'https://github.com/kodhe',
    'name' => 'Kodhe Framework',
    'description' => 'Kodhe Framework application setup',
    'version' => '1.0.0',
    'namespace' => 'Kodhe',
    'aliases' => [
        'Service' => Kodhe\Framework\Container\ServiceHelper::class,
        'Route' => Kodhe\Framework\Http\Routing\Route::class,
        'Request' => Kodhe\Framework\Http\Request::class,
        'Response' => Kodhe\Framework\Http\Response::class,
        'DB' => Kodhe\Framework\Database\Connection\ConnectionManager::class,
        'Model' => Kodhe\Framework\Database\Model::class,
        'Language' => Kodhe\Framework\Support\Language::class,
        'Router' => Kodhe\Framework\Http\Routing\Router::class,
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
        //
        // BUG FIX: entri-entri ini sebelumnya merujuk pada nama-nama pendek
        // (Language::class, Router::class, ...) yang TIDAK ter-import di file
        // ini. Karena setup.php adalah array literal tanpa namespace/use,
        // ::class pada nama tak ter-resolve menghasilkan string lowercase
        // ('language', 'router', ...) — bukan FQCN — sehingga class_alias()
        // gagal dan alias CI_* tidak pernah terdaftar. Semua nilai kini
        // memakai FQCN lengkap.
        'CI_model' => Kodhe\Framework\Database\ORM\CI_Model::class,
        'CI_Model' => Kodhe\Framework\Database\ORM\CI_Model::class,
        'CI_Lang' => Kodhe\Framework\Support\Language::class,
        'CI_Language' => Kodhe\Framework\Support\Language::class,
        'CI_Router' => Kodhe\Framework\Http\Routing\Router::class,
        'CI_Config' => Kodhe\Framework\Config\Config::class,
        'CI_Loader' => Kodhe\Framework\Config\Loaders\FileLoader::class,
        'CI_Controller' => Kodhe\Framework\Http\Controllers\BaseController::class,
        'CI_Hooks' => Kodhe\Framework\Support\Legacy\Hooks::class,
        'CI_Input' => Kodhe\Framework\Support\Legacy\Input::class,
        'CI_URI' => Kodhe\Framework\Support\Legacy\URI::class,
        'CI_Output' => Kodhe\Framework\Support\Legacy\Output::class,
        'CI_Utf8' => Kodhe\Framework\Support\Legacy\Utf8::class,
        'CI_Security' => Kodhe\Framework\Support\Legacy\Security::class,
        'CI_Benchmark' => Kodhe\Framework\Support\Legacy\Benchmark::class,
        'CI_Log' => Kodhe\Framework\Support\Legacy\Log::class,
        'CI_Exceptions' => Kodhe\Framework\Support\Legacy\Exceptions::class,
    ],
    'services' => [
        'cache' => function ($provider) {
            return new \Kodhe\Framework\Cache\Cache();
        }, 
        'calendar' => function ($provider) {
            return new \Kodhe\Framework\Calendar\Calendar();
        }, 
        'cart' => function ($provider) {
            return new \Kodhe\Framework\Cart\Cart();
        }, 
        'driver' => function ($provider) {
            return new \Kodhe\Framework\Driver\Driver();
        }, 
        'email' => function ($provider) {
            return new \Kodhe\Framework\Email\Email();
        }, 
        'encrypt' => function ($provider) {
            return new \Kodhe\Framework\Encrypt\Encrypt();
        }, 
        'encryption' => function ($provider) {
            return new \Kodhe\Framework\Encryption\Encryption();
        }, 
        'form_validation' => function ($provider) {
            return new \Kodhe\Framework\Validation\FormValidation();
        }, 
        'ftp' => function ($provider) {
            return new \Kodhe\Framework\Ftp\Ftp();
        }, 
        'image_lib' => function ($provider) {
            return new \Kodhe\Framework\Image\ImageLib();
        }, 
        'javascript' => function ($provider) {
            return new \Kodhe\Framework\Javascript\Javascript();
        }, 
        'migration' => function ($provider) {
            return new \Kodhe\Framework\Migration\Migration();
        }, 
        'pagination' => function ($provider) {
            return new \Kodhe\Framework\Pagination\Pagination();
        }, 
        'parser' => function ($provider) {
            return new \Kodhe\Framework\Parser\Parser();
        }, 
        'profiler' => function ($provider) {
            return new \Kodhe\Framework\Profiler\Profiler();
        }, 
        'table' => function ($provider) {
            return new \Kodhe\Framework\Table\Table();
        }, 
        'trackback' => function ($provider) {
            return new \Kodhe\Framework\Trackback\Trackback();
        }, 
        'typography' => function ($provider) {
            return new \Kodhe\Framework\Typography\Typography();
        }, 
        'unit_test' => function ($provider) {
            return new \Kodhe\Framework\Test\UnitTest();
        }, 

        'upload' => function ($provider) {
            return new \Kodhe\Framework\Upload\Upload();
        }, 

        'agent' => function ($provider) {
            return new \Kodhe\Framework\Agent\UserAgent();
        }, 

        'xmlrpc' => function ($provider) {
            return new \Kodhe\Framework\Xmlrpc\Xmlrpc();
        }, 

        'xmlrpcs' => function ($provider) {
            return new \Kodhe\Framework\Xmlrpcs\Xmlrpcs();
        }, 

        'zip' => function ($provider) {
            return new \Kodhe\Framework\Zip\Zip();
        }, 
        'session' => function ($provider) {
            return new \Kodhe\Framework\Session\Session();
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
            $class = \Kodhe\Framework\View\ViewFactory::class;
            if (!class_exists($class)) {
                // Package kodhe/view not installed — return null so boot can continue.
                // Controllers that need views should install: composer require kodhe/view
                if (function_exists('log_message')) {
                    log_message('error', 'ViewFactory not found. Install kodhe/view package.');
                }
                return null;
            }
            return new $class();
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