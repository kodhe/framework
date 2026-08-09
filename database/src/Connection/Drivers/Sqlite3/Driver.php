<?php

declare(strict_types=1);

namespace Kodhe\Framework\Database\Connection\Drivers\Sqlite3;

/**
 * SQLite3 Database Adapter Class
 *
 * Note: _DB is an extender class that the app controller
 * creates dynamically based on whether the query builder
 * class is being used or not.
 *
 * @package		CodeIgniter
 * @subpackage	Drivers
 * @category	Database
 * @author		Andrey Andreev
 * @link		https://codeigniter.com/user_guide/database/
 */
class Driver extends \Kodhe\Framework\Database\Query\Builder 
{

	/**
	 * Database driver
	 *
	 * @var	string
	 */
	public $dbdriver = 'sqlite3';

	// --------------------------------------------------------------------

	/**
	 * ORDER BY random keyword
	 *
	 * @var	array
	 */
	protected $_random_keyword = array('RANDOM()', 'RANDOM()');

	/**
	 * @var float Query execution time in seconds
	 */
	public $query_time = 0;
	
	/**
	 * @var float Query execution time in milliseconds
	 */
	public $query_time_ms = 0;
	
	/**
	 * @var float Start time for current query
	 */
	protected $_query_start_time = 0;
	
	/**
	 * @var array Query log with execution times
	 */
	public $query_log = array();
	
	/**
	 * @var bool Enable query logging
	 */
	public $enable_query_log = false;

	// --------------------------------------------------------------------

	/**
	 * Non-persistent database connection
	 *
	 * @param	bool	$persistent
	 * @return	SQLite3
	 */
	public function db_connect($persistent = FALSE)
	{
		if ($persistent)
		{
			log_message('debug', 'SQLite3 doesn\'t support persistent connections');
		}

		try
		{
			return ( ! $this->password)
				? new SQLite3($this->database)
				: new SQLite3($this->database, SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE, $this->password);
		}
		catch (Exception $e)
		{
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

		$version = SQLite3::version();
		return $this->data_cache['version'] = $version['versionString'];
	}

	// --------------------------------------------------------------------

	/**
	 * Execute the query with timing
	 *
	 * @todo	Implement use of SQLite3::querySingle(), if needed
	 * @param	string	$sql
	 * @return	mixed	SQLite3Result object or bool
	 */
	protected function _execute($sql)
	{
		// Start timing
		$this->_query_start_time = microtime(true);
		
		// Execute query
		$result = $this->is_write_type($sql)
			? $this->conn_id->exec($sql)
			: $this->conn_id->query($sql);
		
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
				'timestamp' => date('Y-m-d H:i:s'),
				'type' => $this->is_write_type($sql) ? 'write' : 'read'
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
		return $this->conn_id->exec('BEGIN TRANSACTION');
	}

	// --------------------------------------------------------------------

	/**
	 * Commit Transaction
	 *
	 * @return	bool
	 */
	protected function _trans_commit()
	{
		return $this->conn_id->exec('END TRANSACTION');
	}

	// --------------------------------------------------------------------

	/**
	 * Rollback Transaction
	 *
	 * @return	bool
	 */
	protected function _trans_rollback()
	{
		return $this->conn_id->exec('ROLLBACK');
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
		return $this->conn_id->escapeString($str);
	}

	// --------------------------------------------------------------------

	/**
	 * Affected Rows
	 *
	 * @return	int
	 */
	public function affected_rows()
	{
		return $this->conn_id->changes();
	}

	// --------------------------------------------------------------------

	/**
	 * Insert ID
	 *
	 * @return	int
	 */
	public function insert_id()
	{
		return $this->conn_id->lastInsertRowID();
	}

	// --------------------------------------------------------------------

	/**
	 * Show table query
	 *
	 * Generates a platform-specific query string so that the table names can be fetched
	 *
	 * @param	bool	$prefix_limit
	 * @return	string
	 */
	protected function _list_tables($prefix_limit = FALSE)
	{
		return 'SELECT "NAME" FROM "SQLITE_MASTER" WHERE "TYPE" = \'table\''
			.(($prefix_limit !== FALSE && $this->dbprefix != '')
				? ' AND "NAME" LIKE \''.$this->escape_like_str($this->dbprefix).'%\' '.sprintf($this->_like_escape_str, $this->_like_escape_chr)
				: '');
	}

	// --------------------------------------------------------------------

	/**
	 * Fetch Field Names
	 *
	 * @param	string	$table	Table name
	 * @return	array
	 */
	public function list_fields($table)
	{
		if (($result = $this->query('PRAGMA TABLE_INFO('.$this->protect_identifiers($table, TRUE, NULL, FALSE).')')) === FALSE)
		{
			return FALSE;
		}

		$fields = array();
		foreach ($result->result_array() as $row)
		{
			$fields[] = $row['name'];
		}

		return $fields;
	}

	// --------------------------------------------------------------------

	/**
	 * Returns an object with field data
	 *
	 * @param	string	$table
	 * @return	array
	 */
	public function field_data($table)
	{
		if (($query = $this->query('PRAGMA TABLE_INFO('.$this->protect_identifiers($table, TRUE, NULL, FALSE).')')) === FALSE)
		{
			return FALSE;
		}

		$query = $query->result_array();
		if (empty($query))
		{
			return FALSE;
		}

		$retval = array();
		for ($i = 0, $c = count($query); $i < $c; $i++)
		{
			$retval[$i]			= new stdClass();
			$retval[$i]->name		= $query[$i]['name'];
			$retval[$i]->type		= $query[$i]['type'];
			$retval[$i]->max_length		= NULL;
			$retval[$i]->default		= $query[$i]['dflt_value'];
			$retval[$i]->primary_key	= isset($query[$i]['pk']) ? (int) $query[$i]['pk'] : 0;
		}

		return $retval;
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
		return array('code' => $this->conn_id->lastErrorCode(), 'message' => $this->conn_id->lastErrorMsg());
	}

	// --------------------------------------------------------------------

	/**
	 * Replace statement
	 *
	 * Generates a platform-specific replace string from the supplied data
	 *
	 * @param	string	$table	Table name
	 * @param	array	$keys	INSERT keys
	 * @param	array	$values	INSERT values
	 * @return	string
	 */
	protected function _replace($table, $keys, $values)
	{
		return 'INSERT OR '.parent::_replace($table, $keys, $values);
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
		return 'DELETE FROM '.$table;
	}

	// --------------------------------------------------------------------

	/**
	 * Close DB Connection
	 *
	 * @return	void
	 */
	protected function _close()
	{
		$this->conn_id->close();
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

	/**
	 * Get query log filtered by type (read/write)
	 * 
	 * @param string $type 'read' or 'write'
	 * @return array
	 */
	public function get_query_log_by_type($type)
	{
		$filtered = array();
		foreach ($this->query_log as $log) {
			if (isset($log['type']) && $log['type'] === $type) {
				$filtered[] = $log;
			}
		}
		return $filtered;
	}

	/**
	 * Get total execution time for read queries
	 * 
	 * @return float
	 */
	public function get_total_read_time()
	{
		$total = 0;
		foreach ($this->query_log as $log) {
			if (isset($log['type']) && $log['type'] === 'read') {
				$total += $log['time'];
			}
		}
		return $total;
	}

	/**
	 * Get total execution time for write queries
	 * 
	 * @return float
	 */
	public function get_total_write_time()
	{
		$total = 0;
		foreach ($this->query_log as $log) {
			if (isset($log['type']) && $log['type'] === 'write') {
				$total += $log['time'];
			}
		}
		return $total;
	}
}
