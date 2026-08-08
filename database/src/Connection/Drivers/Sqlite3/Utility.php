<?php

declare(strict_types=1);

namespace Kodhe\Database\Connection\Drivers\Sqlite3;

/**
 * SQLite3 Utility Class
 *
 * @category	Database
 * @author	Andrey Andreev
 * @link	https://codeigniter.com/user_guide/database/
 */
class Utility extends \Kodhe\Database\Query\Grammar 
{

	/**
	 * Export
	 *
	 * @param	array	$params	Preferences
	 * @return	mixed
	 */
	protected function _backup($params = array())
	{
		// Not supported
		return $this->db->display_error('db_unsupported_feature');
	}

}
