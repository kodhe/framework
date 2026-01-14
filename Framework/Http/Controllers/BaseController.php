<?php namespace Kodhe\Framework\Http\Controllers;
use Kodhe\Framework\View\ViewFactory;
use Kodhe\Framework\Config\Loaders\FileLoader;

class BaseController extends Controller {
    public function __construct()
    {
        
        parent::__construct();

        
        app()->load->helper([
            'url',
            'template_asset_helper',
            'string',
            'template',
            'routing'
        ]);

        app()->load->library(['user_agent']);
        

        
        app()->set('theme', app('view'));
    }


}