<?php
# Copyright (C) 2017 Valerio Bozzolan
#
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program.  If not, see <http://www.gnu.org/licenses/>.

class Query {
	private $db;

	private $class_name;

	private $selectFields = [];
	private $tables = [];
	private $groups = [];
	private $conditions;
	private $offset;
	private $rowCount;
	private $orders;

	function __construct(& $db = null) {
		if( ! $db ) {
			$db = expect('db');
		}
		$this->db = $db; // Dipendency injection
	}

	/**
	 * Construct shortcut.
	 *
	 * @param string $class_name Class to encapsulate the database result
	 * @return Query
	 */
	static function factory($class_name = null) {
		$t = new self();
		$class_name and $t->defaultClass($class_name);
		return $t;
	}


	/**
	 * Selected fields (SELECT).
	 *
	 * @param string|array $field
	 * @return Query
	 */
	function select(...$field) {
		self::appendInArray($field, $this->selectFields);
		return $this;
	}

	/**
	 * Selected tables without prefix.
	 *
	 * @param string|array $table Table/tables
	 * @note I wanted to use "use()", but it's reserved.
	 * @return Query
	 */
	function from(...$table) {
		self::appendInArray($table, $this->tables);
		return $this;
	}

	/**
	 * Group by
	 * @param string|array $groups Group by
	 * @return Query
	 */
	function groupBy(...$groups) {
		self::appendInArray($groups, $this->groups);
		return $this;
	}

	/**
	 * You have double-selected a table but that wasn't your goal.
	 *
	 * @return Query
	 */
	function uniqueTables() {
		$this->tables = array_unique($this->tables);
		return $this;
	}

	/**
	 * Query condition.
	 *
	 * @param string $condition Something as 'field = 1'
	 * @return Query
	 */
	function where($condition, $glue = 'AND') {
		if( isset( $this->conditions) ) {
			$this->conditions .= " $glue ";
		}
		$this->conditions .= $condition;
		return $this;
	}

	/**
	 * Intendeed to be used for PRIMARY KEY joins.
	 *
	 * @param string $one Result set field
	 * @param string $two Result set field
	 * @return Query
	 */
	function equals($one, $two) {
		return $this->where("$one = $two");
	}

	/**
	 * Intended to compare a property with a number.
	 *
	 * @param string $one Column name
	 * @param int $value Value
	 */
	function whereInt($column, $value) {
		return $this->equals($column, (int) $value);
	}

	/**
	 * Intended to compare a property with a string.
	 *
	 * @param string $one Column name
	 * @param string $value Value
	 */
	function whereStr($column, $value) {
		$value = esc_sql($value);
		return $this->equals($column, "'$value'");
	}

	/**
	 * LIMIT count, offset.
	 *
	 * @param int $row_count Max numbers of elements.
	 * @param int $offset Starting offset
	 * @return Query
	 */
	function limit($row_count, $offset = null) {
		$this->rowCount = $row_count;
		$this->offset = $offset;
		return $this;
	}

	/**
	 * Handy shortcut for `something IN (values)` condition.
	 *
	 * @param string $heystack Field.
	 * @param string|array $needles Values to compare.
	 */
	function whereSomethingIn($heystack, $needles, $glue = 'AND', $not_in = false) {
		force_array($needles);

		$n_needles = count($needles);
		if( $n_needles === 1 ) {
			$this->where(
				sprintf("%s %s '%s'",
					$heystack,
					($not_in) ? '!=' : '=',
					esc_sql( $needles[0] )
				),
				$glue
			);
			return $this;
		}

		$values = '';
		for($i=0; $i<$n_needles; $i++) {
			if($i != 0) {
				$values .= ', ';
			}
			$values .= single_quotes( esc_sql($needles[ $i ]) );
		}
		if( $values !== '') {
			if($not_in) {
				$this->appendCondition("$heystack NOT IN ($values)", $glue);
			} else {
				$this->appendCondition("$heystack IN ($values)", $glue);
			}
		}
		return $this;
	}

	/**
	 * Handy shortcut for `something NOT IN (values)` condition.
	 *
	 * @param string $heystack Field.
	 * @param string|array $needles Values to compare.
	 */
	function whereSomethingNotIn($heystack, $needles, $glue = 'AND') {
		$this->appendConditionSomethingIn($heystack, $needles, $glue, true); // See true
		return $this;
	}

	function getFrom() {
		return $this->db->getTables( $this->tables );
	}

	function getSelect() {
		if( count( $this->selectFields ) === 0 ) {
			return '*';
		}
		return implode(', ', $this->selectFields);
	}

	function getWhere() {
		return $this->conditions;
	}

	function getGroupBy() {
		return implode(', ', $this->groups);
	}

	function orderBy($order_by) {
		if( isset( $this->orders ) ) {
			$this->orders .= ', ';
		}
		$this->orders .= $order_by;
		return $this;
	}

	/**
	 * @return string SQL query
	 */
	function getQuery() {
		$sql = "SELECT {$this->getSelect()} FROM {$this->getFrom()}";
		if($this->conditions) {
			$sql .= " WHERE {$this->getWhere()}";
		}
		if($this->groups) {
			$sql .= " GROUP BY {$this->getGroupBy()}";
		}
		if($this->orders) {
			$sql .= " ORDER BY {$this->orders}";
		}
		if($this->rowCount) {
			$sql .= ' LIMIT ';
			if( $this->offset ) {
				$sql .= "{$this->offset}, ";
			}
			$sql .= $this->rowCount;
		}
		return $sql;
	}

	/**
	 * Set the default class to incapsulate the result set.
	 *
	 * @param string $class_name Class name
	 * @return Query
	 */
	function defaultClass($class_name) {
		$this->class_name = $class_name;
		return $this;
	}

	function getDefaultClass() {
		$c = $this->class_name;
		return isset( $c ) ? $c : null;
	}

	/**
	 * @see DB#query()
	 */
	function query() {
		return $this->db->query( $this->getQuery() );
	}

	/**
	 * Get the array of result sets encapsulated in the specified class.
	 *
	 * @param string $class_name
	 * @see DB#getResults()
	 * @return array
	 */
	function queryResults($class_name = null, $params = [] ) {
		$class_name = $class_name ? $class_name : $this->getDefaultClass();
		return $this->db->getResults( $this->getQuery(), $class_name, $params );
	}

	/**
	 * Get the result set encapsulated in the specified class.
	 *
	 * @param string $class_name
	 * @see DB#getRow()
	 * @return null|Object
	 */
	function queryRow($class_name = null, $params = []) {
		$class_name = $class_name ? $class_name : $this->getDefaultClass();
		return $this->db->getRow( $this->getQuery(), $class_name, $params );
	}

	/**
	 * Get the specified column from the first result set.
	 * @param string $column_name
	 * @see DB#getValue()
	 * @return mixed
	 */
	function queryValue($column_name) {
		return $this->db->getValue( $this->getQuery(), $column_name );
	}

	private static function appendInArray($values, & $array) {

		// Retrocompatibility patch
		if( isset( $values[0] ) && is_array( $values[0] ) ) {
			$values = $values[0];
		}

		foreach($values as $value) {
			if( $value && ! in_array($value, $array, true) ) {
				$array[] = $value;
			}
		}
	}
}

