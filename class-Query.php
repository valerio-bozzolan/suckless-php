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

	private $class_name = null;

	private $selectFields = [];
	private $tables = [];
	private $conditions = null;
	private $offset;
	private $rowCount;
	private $orders = null;

	function __construct(& $db = null) {
		if(!$db) {
			expect('db');
			$db = & $GLOBALS['db'];
		}
		$this->db = $db; // Dipendency injection
	}

	function factory($class_name = null) {
		$t = new self();
		$class_name and $t->defaultClass($class_name);
		return $t;
	}

	/**
	 * I wanted to use "use()", but it's reserved.
	 */
	function from($t) {
		self::appendInArray($t, $this->tables);
		return $this;
	}

	function uniqueTables() {
		$this->tables = array_unique($this->tables);
		return $this;
	}

	function select($f) {
		self::appendInArray($f, $this->selectFields);
		return $this;
	}

	function where($c, $glue = 'AND') {
		if($this->conditions !== null) {
			$this->conditions .= " $glue ";
		}
		$this->conditions .= $c;
		return $this;
	}

	function limit($row_count, $offset = null) {
		$this->rowCount = $row_count;
		$this->offset = $offset;
		return $this;
	}

	function whereSomethingIn($heystack, $needles, $glue = 'AND', $not_in = false) {
		force_array($needles);

		$n_needles = count($needles);
		if( $n_needles === 1 ) {
			$this->appendCondition(
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

	function whereSomethingNotIn($heystack, $needles, $glue = 'AND') {
		$this->appendConditionSomethingIn($heystack, $needles, $glue, true); // See true
		return $this;
	}

	private static function appendInArray($values, & $array) {
		force_array($values);

		foreach($values as $value) {
			if( $value && ! in_array($value, $array, true) ) {
				$array[] = $value;
			}
		}
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

	function orderBy($order_by) {
		if($this->orders !== null) {
			$this->orders .= ', ';
		}
		$this->orders .= $order_by;
		return $this;
	}

	function getQuery() {
		$sql = "SELECT {$this->getSelect()} FROM {$this->getFrom()}";
		if($this->conditions) {
			$sql .= " WHERE {$this->getWhere()}";
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

	function defaultClass($class_name) {
		$this->class_name = $class_name;
		return $this;
	}

	function getDefaultClass() {
		$c = $this->class_name;
		return isset( $c ) ? $c : null;
	}

	function query() {
		return $this->db->query( $this->getQuery() );
	}

	function queryResults($class_name = null, $params = [] ) {
		$class_name = $class_name ? $class_name : $this->getDefaultClass();
		return $this->db->getResults( $this->getQuery(), $class_name, $params );
	}

	function queryRow($class_name = null, $params = []) {
		$class_name = $class_name ? $class_name : $this->getDefaultClass();
		return $this->db->getRow( $this->getQuery(), $class_name, $params );
	}

	function queryValue($column_name) {
		return $this->db->getValue( $this->getQuery(), $column_name );
	}
}

