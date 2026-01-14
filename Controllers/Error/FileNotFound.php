<?php namespace Kodhe\Controllers\Error;

use Kodhe\Framework\Http\Controllers\BaseController;

class FileNotFound extends BaseController
{
    public function index()
    {
      $data = [
        'title'=>'404 Page Not Found',
        'heading'=>'The page you are looking for might have been removed or is temporarily unavailable.',
        'message'=>'<p>The page you requested was not found.</p>'
      ];

      //show_404();
		return app('view')->render('errors.html.error_404', $data, false);

    }
}