<?php

namespace Kodhe\Framework\Database\ORM;

use Exception;


class LegacyModel {

	/**
	 * Class constructor
	 *
	 * @link	https://github.com/bcit-ci/CodeIgniter/issues/5332
	 * @return	void
	 */
	public function __construct() {}


	public function __set($name, $value)
	{
		if(!empty(get_instance()->has($name))) {
			return false;
		}

		return get_instance()->set($name, $value);
	}

	public function __get($name)
	{
		if(!empty(get_instance()->has($name))) {
			return get_instance()->$name;
		}

		return false;
	}

}

