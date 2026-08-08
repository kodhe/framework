<?php

declare(strict_types=1);

namespace Kodhe\Framework\Controllers\Error;

use Kodhe\Http\Controllers\BaseController;

class CPException extends BaseController
{
    public function show_404()
    {
		$data = ['title'=>'Dashboard'];
		$data['news'] = [];


		return view('pages.home', $data, false);

    }
}
