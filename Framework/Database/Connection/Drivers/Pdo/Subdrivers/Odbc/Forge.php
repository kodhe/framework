<?php namespace Kodhe\Framework\Database\Connection\Drivers\Pdo\Subdrivers\Odbc;

/**
 * PDO ODBC Forge Class
 *
 * @category	Database
 * @author		EllisLab Dev Team
 * @link		https://codeigniter.com/database/
 */
class Forge extends \Kodhe\Framework\Database\Connection\Drivers\Pdo\Forge {

	/**
	 * UNSIGNED support
	 *
	 * @var	bool|array
	 */
	protected $_unsigned		= FALSE;

	// --------------------------------------------------------------------

	/**
	 * Field attribute AUTO_INCREMENT
	 *
	 * @param	array	&$attributes
	 * @param	array	&$field
	 * @return	void
	 */
	protected function _attr_auto_increment(&$attributes, &$field)
	{
		// Not supported (in most databases at least)
	}

}
