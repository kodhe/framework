<?php

declare(strict_types=0);

namespace Kodhe\Framework\Database\Connection\Drivers\Pdo;

/**
 * PDO Database Adapter Class
 *
 * Note: _DB is an extender class that the app controller
 * creates dynamically based on whether the query builder
 * class is being used or not.
 *
 * @package		CodeIgniter
 * @subpackage	Drivers
 * @category	Database
 * @author		EllisLab Dev Team
 * @link		https://codeigniter.com/user_guide/database/
 */
 use PDO;

class Driver extends \Kodhe\Framework\Database\Query\Builder 
{

	/**
	 * Database driver
	 *
	 * @var	string
	 */
	protected $dbdriver = 'pdo';

	/**
	 * PDO Options
	 *
	 * @var	array
	 */
	protected $options = array();
	protected $stricton;
	protected $compress;
	
	/**
	 * @var float Query execution time in seconds
	 */
	protected $query_time = 0;
	
	/**
	 * @var float Query execution time in milliseconds
	 */
	protected $query_time_ms = 0;
	
	/**
	 * @var float Start time for current query
	 */
	protected $_query_start_time = 0;
	
	/**
	 * @var array Query log with execution times
	 */
	protected $query_log = array();
	
	/**
	 * @var bool Enable query logging
	 */
	protected $enable_query_log = false;

	// --------------------------------------------------------------------

	/**
	 * Class constructor
	 *
	 * Validates the DSN string and/or detects the subdriver.
	 *
	 * @param	array	$params
	 * @return	void
	 */
	public function __construct($params)
	{
		parent::__construct($params);

		if (preg_match('/([^:]+):/', $this->dsn, $match) && count($match) === 2)
		{
			// If there is a minimum valid dsn string pattern found, we're done
			// This is for general PDO users, who tend to have a full DSN string.
			$this->subdriver = $match[1];
			return;
		}
		// Legacy support for DSN specified in the hostname field
		elseif (preg_match('/([^:]+):/', $this->hostname, $match) && count($match) === 2)
		{
			$this->dsn = $this->hostname;
			$this->hostname = NULL;
			$this->subdriver = $match[1];
			return;
		}
		elseif (in_array($this->subdriver, array('mssql', 'sybase'), TRUE))
		{
			$this->subdriver = 'dblib';
		}
		elseif ($this->subdriver === '4D')
		{
			$this->subdriver = '4d';
		}
		elseif ( ! in_array($this->subdriver, array('4d', 'cubrid', 'dblib', 'firebird', 'ibm', 'informix', 'mysql', 'oci', 'odbc', 'pgsql', 'sqlite', 'sqlsrv'), TRUE))
		{
			log_message('error', 'PDO: Invalid or non-existent subdriver');

			if ($this->db_debug)
			{
				show_error('Invalid or non-existent PDO subdriver');
			}
		}

		$this->dsn = NULL;
	}

	// --------------------------------------------------------------------

	/**
	 * Database connection
	 *
	 * @param	bool	$persistent
	 * @return	object
	 */
	public function db_connect($persistent = FALSE)
	{
		if ($persistent === TRUE)
		{
			$this->options[PDO::ATTR_PERSISTENT] = TRUE;
		}

		try
		{
			return new PDO($this->dsn, $this->username, $this->password, $this->options);
		}
		catch (PDOException $e)
		{
			if ($this->db_debug && empty($this->failover))
			{
				$this->display_error($e->getMessage(), '', TRUE);
			}

			return FALSE;
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Database version number
	 *
	 * @return	string
	 */
	public function version()
	{
		if (isset($this->data_cache['version']))
		{
			return $this->data_cache['version'];
		}

		// Not all subdrivers support the getAttribute() method
		try
		{
			return $this->data_cache['version'] = $this->conn_id->getAttribute(PDO::ATTR_SERVER_VERSION);
		}
		catch (PDOException $e)
		{
			return parent::version();
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Execute the query with timing
	 *
	 * @param	string	$sql	SQL query
	 * @return	mixed
	 */
	protected function _execute($sql)
	{
		// Start timing
		$this->_query_start_time = microtime(true);
		
		// Execute query
		$result = $this->conn_id->query($sql);
		
		// Calculate execution time
		$end_time = microtime(true);
		$this->query_time = $end_time - $this->_query_start_time;
		$this->query_time_ms = round($this->query_time * 1000, 2);
		
		// Log query if enabled
		if ($this->enable_query_log) {
			$this->query_log[] = array(
				'sql' => $sql,
				'time' => $this->query_time,
				'time_ms' => $this->query_time_ms,
				'timestamp' => date('Y-m-d H:i:s')
			);
		}
		
		return $result;
	}

	// --------------------------------------------------------------------

	/**
	 * Begin Transaction
	 *
	 * @return	bool
	 */
	protected function _trans_begin()
	{
		return $this->conn_id->beginTransaction();
	}

	// --------------------------------------------------------------------

	/**
	 * Commit Transaction
	 *
	 * @return	bool
	 */
	protected function _trans_commit()
	{
		return $this->conn_id->commit();
	}

	// --------------------------------------------------------------------

	/**
	 * Rollback Transaction
	 *
	 * @return	bool
	 */
	protected function _trans_rollback()
	{
		return $this->conn_id->rollBack();
	}

	// --------------------------------------------------------------------

	/**
	 * Platform-dependent string escape
	 *
	 * @param	string
	 * @return	string
	 */
	protected function _escape_str($str)
	{
		// Escape the string
		$str = $this->conn_id->quote($str);

		// If there are duplicated quotes, trim them away
		return ($str[0] === "'")
			? substr($str, 1, -1)
			: $str;
	}

	// --------------------------------------------------------------------

	/**
	 * Affected Rows
	 *
	 * @return	int
	 */
	public function affected_rows()
	{
		return is_object($this->result_id) ? $this->result_id->rowCount() : 0;
	}

	// --------------------------------------------------------------------

	/**
	 * Insert ID
	 *
	 * @param	string	$name
	 * @return	int
	 */
	public function insert_id($name = NULL)
	{
		return $this->conn_id->lastInsertId($name);
	}

	// --------------------------------------------------------------------

	/**
	 * Field data query
	 *
	 * Generates a platform-specific query so that the column data can be retrieved
	 *
	 * @param	string	$table
	 * @return	string
	 */
	protected function _field_data($table)
	{
		return 'SELECT TOP 1 * FROM '.$this->protect_identifiers($table);
	}

	// --------------------------------------------------------------------

	/**
	 * Error
	 *
	 * Returns an array containing code and message of the last
	 * database error that has occurred.
	 *
	 * @return	array
	 */
	public function error()
	{
		$error = array('code' => '00000', 'message' => '');
		$pdo_error = $this->conn_id->errorInfo();

		if (empty($pdo_error[0]))
		{
			return $error;
		}

		$error['code'] = isset($pdo_error[1]) ? $pdo_error[0].'/'.$pdo_error[1] : $pdo_error[0];
		if (isset($pdo_error[2]))
		{
			 $error['message'] = $pdo_error[2];
		}

		return $error;
	}

	// --------------------------------------------------------------------

	/**
	 * Truncate statement
	 *
	 * Generates a platform-specific truncate string from the supplied data
	 *
	 * If the database does not support the TRUNCATE statement,
	 * then this method maps to 'DELETE FROM table'
	 *
	 * @param	string	$table
	 * @return	string
	 */
	protected function _truncate($table)
	{
		return 'TRUNCATE TABLE '.$table;
	}

	// ========================================================================
	// QUERY TIME METHODS
	// ========================================================================

	/**
	 * Get last query execution time in seconds
	 * 
	 * @return float
	 */
	public function get_query_time()
	{
		return $this->query_time;
	}

	/**
	 * Get last query execution time in milliseconds
	 * 
	 * @return float
	 */
	public function get_query_time_ms()
	{
		return $this->query_time_ms;
	}

	/**
	 * Get formatted query time (e.g., "12.34ms")
	 * 
	 * @return string
	 */
	public function get_query_time_formatted()
	{
		return $this->query_time_ms . 'ms';
	}

	/**
	 * Enable query logging
	 * 
	 * @return $this
	 */
	public function enable_query_log()
	{
		$this->enable_query_log = true;
		return $this;
	}

	/**
	 * Disable query logging
	 * 
	 * @return $this
	 */
	public function disable_query_log()
	{
		$this->enable_query_log = false;
		return $this;
	}

	/**
	 * Get query log
	 * 
	 * @return array
	 */
	public function get_query_log()
	{
		return $this->query_log;
	}

	/**
	 * Clear query log
	 * 
	 * @return $this
	 */
	public function clear_query_log()
	{
		$this->query_log = array();
		return $this;
	}

	/**
	 * Get total execution time of all logged queries
	 * 
	 * @return float
	 */
	public function get_total_query_time()
	{
		$total = 0;
		foreach ($this->query_log as $log) {
			$total += $log['time'];
		}
		return $total;
	}

	/**
	 * Get total execution time in milliseconds
	 * 
	 * @return float
	 */
	public function get_total_query_time_ms()
	{
		return round($this->get_total_query_time() * 1000, 2);
	}

	/**
	 * Get number of queries executed
	 * 
	 * @return int
	 */
	public function get_query_count()
	{
		return count($this->query_log);
	}
}
