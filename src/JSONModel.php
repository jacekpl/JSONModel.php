<?php

/**
 * A model controller.
 */
class JSONModel {
	/**
	 * This model controller's SQL Abstraction Layer
	 *
	 * @var SQLAbstract $sqlAbstract
	 */
	protected $sqlAbstract;
	/**
	 * This model controller's application domain name.
	 *
	 * @var string $domain
	 */
	protected $domain;
	/**
	 * This model controller's name
	 *
	 * @var string $name
	 */
	protected $name;
	/**
	 * This model controller's columns
	 *
	 * @var array $columns
	 */
	protected $columns;
	/**
	 *
	 */
	protected $jsonColumn = NULL;
	/**
	 *
	 */
	protected $types;
	/**
	 *
	 */
	function __construct($sqlAbstract, $domain, $name, $columns, $types) {
		$this->sqlAbstract = $sqlAbstract;
		$this->domain = $domain;
		$this->name = $name;
		$this->columns = $columns;
		$this->types = $types;
		if (array_key_exists($name.'_json', $columns)) {
			$this->jsonColumn = $name.'_json';
		}
	}
	/**
	 * Return a new exception to be throwed by the model's methods.
	 *
	 * @param string $message
	 * @param Exception $previous
	 * @return Exception
	 */
	function exception ($message, $previous=NULL) {
		return new Exception($message, $previous);
	}
	/**
	 * Return a new JSONMessage to be used by the model's methods.
	 *
	 * @param array $map
	 * @param string $encoded
	 * @return JSONMessage
	 */
	function message ($map, $encoded=NULL) {
		return new JSONMessage($map, $encoded);
	}
	/**
	 * Return the qualified name of this model (ie: its unprefixed table name)
	 *
	 * @return string
	 */
	function qualifiedName () {
		return $this->domain.$this->name;
	}
	/**
	 *
	 */
	function create () {
		$this->sqlAbstract->createTable($this->qualifiedName(), $this->columns);
	}
	/**
	 * Cast a row into a map using the type caster defined for this model.
	 *
	 * @param array $row
	 * @return JSONMessage
	 */
	function cast ($row, $map) {
		foreach ($row as $column => $value) {
			if (array_key_exists($column, $this->types)) {
				$map[$column] = call_user_func_array(
					$this->types[$column], array($row[$column])
					);
			} else {
				$map[$column] = $row[$column];
			}
		}
		return $map;
	}
	/**
	 * Map a row into a message, eventually using this model's JSON column if
	 * it has been defined.
	 *
	 * @param array $row
	 * @return JSONMessage
	 */
	function map ($row) {
		if (array_key_exists($this->jsonColumn, $row)) {
			$encoded = $row[$this->jsonColumn];
			$map = json_decode($encoded, TRUE);
			unset($row[$column]);
			return $this->message($this->cast($row, $map), $encoded);
		} else {
			return $this->message($this->cast($row, array()));
		}
	}
	/**
	 *
	 */
	function fetchById ($id) {
		return $this->map($this->sqlAbstract->getRowById(
			$this->qualifiedName(), $id, $this->name
			));
	}
	/**
	 *
	 */
	function fetchByIds ($ids) {
		$rows = $this->sqlAbstract->getRowsByIds(
			$this->qualifiedName(), $ids, $this->name
			);
		return array_map(array($this, 'map'), $rows);
	}
	/**
	 *
	 */
	function select ($options=array()) {
		$rows = $this->sqlAbstract->select($this->qualifiedName(), $options);
		return array_map(array($this, 'map'), $rows);
	}
	/**
	 *
	 */
	function count ($options=array()) {
		return $this->sqlAbstract->count($this->qualifiedName(), $options);
	}
	/**
	 * Map a message's map into a row, eventually encoding a JSON column if it
	 * exists in the model.
	 *
	 * @param array $map
	 * @return array
	 */
	function row ($map) {
		if ($this->jsonColumn === NULL) {
			return $map;
		}
		$json = array();
		$row = array();
		foreach ($map as $key => $value) {
			if (is_scalar($value)) {
				$row[$key] = $value;
				$json[$key] = $value;
			} else {
				$json[$key] = $value;
			}
		}
		if (!empty($json)) {
			$row[$this->jsonColumn] = json_encode($json);
		}
		return $row;
	}
	/**
	 * Insert a message's into this model's table, return the inserted ID.
	 *
	 * @param JSONMessage $message
	 * @return integer
	 */
	function insert ($message) {
		return $this->sqlAbstract->insert(
			$this->qualifiedName(), $this->row($message->map)
			);
	}
	/**
	 * Insert a message's into this model's table, return the number of affected rows.
	 *
	 * @param JSONMessage $message
	 * @return integer
	 */
	function replace ($message) {
		return $this->sqlAbstract->replace(
			$this->qualifiedName(), $this->row($message->map)
			);
	}
}
