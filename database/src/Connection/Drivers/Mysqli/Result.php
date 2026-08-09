<?php

declare(strict_types=0);

namespace Kodhe\Framework\Database\Connection\Drivers\Mysqli;

use Kodhe\Framework\Database\Query\Result as QueryResult;

class Result extends QueryResult 
{

	public function num_rows()
	{
		if (is_int($this->num_rows))
		{
			return $this->num_rows;
		}
		elseif ( ! $this->result_id)
		{
			return 0;
		}

		return $this->num_rows = $this->result_id->num_rows;
	}

	public function num_fields()
	{
		if ( ! $this->result_id)
		{
			return 0;
		}
		return $this->result_id->field_count;
	}

	public function list_fields()
	{
		if ( ! $this->result_id)
		{
			return array();
		}
		$field_names = array();
		$this->result_id->field_seek(0);
		while ($field = $this->result_id->fetch_field())
		{
			$field_names[] = $field->name;
		}

		return $field_names;
	}

	public function field_data()
	{
		if ( ! $this->result_id)
		{
			return array();
		}
		$retval = array();
		$field_data = $this->result_id->fetch_fields();
		for ($i = 0, $c = count($field_data); $i < $c; $i++)
		{
			$retval[$i]			= new stdClass();
			$retval[$i]->name		= $field_data[$i]->name;
			$retval[$i]->type		= static::_get_field_type($field_data[$i]->type);
			$retval[$i]->max_length		= $field_data[$i]->max_length;
			$retval[$i]->primary_key	= (int) ($field_data[$i]->flags & MYSQLI_PRI_KEY_FLAG);
			$retval[$i]->default		= $field_data[$i]->def;
		}

		return $retval;
	}

	private static function _get_field_type($type)
	{
		static $map;
		isset($map) OR $map = array(
			MYSQLI_TYPE_DECIMAL     => 'decimal',
			MYSQLI_TYPE_BIT         => 'bit',
			MYSQLI_TYPE_TINY        => 'tinyint',
			MYSQLI_TYPE_SHORT       => 'smallint',
			MYSQLI_TYPE_INT24       => 'mediumint',
			MYSQLI_TYPE_LONG        => 'int',
			MYSQLI_TYPE_LONGLONG    => 'bigint',
			MYSQLI_TYPE_FLOAT       => 'float',
			MYSQLI_TYPE_DOUBLE      => 'double',
			MYSQLI_TYPE_TIMESTAMP   => 'timestamp',
			MYSQLI_TYPE_DATE        => 'date',
			MYSQLI_TYPE_TIME        => 'time',
			MYSQLI_TYPE_DATETIME    => 'datetime',
			MYSQLI_TYPE_YEAR        => 'year',
			MYSQLI_TYPE_NEWDATE     => 'date',
			MYSQLI_TYPE_INTERVAL    => 'interval',
			MYSQLI_TYPE_ENUM        => 'enum',
			MYSQLI_TYPE_SET         => 'set',
			MYSQLI_TYPE_TINY_BLOB   => 'tinyblob',
			MYSQLI_TYPE_MEDIUM_BLOB => 'mediumblob',
			MYSQLI_TYPE_BLOB        => 'blob',
			MYSQLI_TYPE_LONG_BLOB   => 'longblob',
			MYSQLI_TYPE_STRING      => 'char',
			MYSQLI_TYPE_VAR_STRING  => 'varchar',
			MYSQLI_TYPE_GEOMETRY    => 'geometry'
		);

		return isset($map[$type]) ? $map[$type] : $type;
	}

	public function free_result()
	{
		if (is_object($this->result_id))
		{
			$this->result_id->free();
			$this->result_id = FALSE;
		}
	}

	public function data_seek($n = 0)
	{
		if ( ! $this->result_id)
		{
			return FALSE;
		}
		return $this->result_id->data_seek($n);
	}

	protected function _fetch_assoc()
	{
		if ( ! $this->result_id)
		{
			return NULL;
		}
		return $this->result_id->fetch_assoc();
	}

	protected function _fetch_object($class_name = 'stdClass')
	{
		if ( ! $this->result_id)
		{
			return NULL;
		}
		return $this->result_id->fetch_object($class_name);
	}

}
