<?php
# Copyright (C) 2015 Valerio Bozzolan
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

class DynamicQuery {
	private $db;

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

	public function useTable($t) {
		self::appendInArray($t, $this->tables);
	}

	public function selectField($f) {
		self::appendInArray($f, $this->selectFields);
	}

	public function appendCondition($c, $glue = 'AND') {
		if($this->conditions !== null) {
			$this->conditions .= " $glue ";
		}
		$this->conditions .= $c;
	}

	public function setLimit($row_count, $offset = null) {
		$this->rowCount = $row_count;
		$this->offset = $offset;
	}

	public function appendConditionSomethingIn($heystack, $needles, $glue = 'AND', $not_in = false) {
		if( ! is_array( $needles ) ) {
			$needles = single_quotes( esc_sql( $needles ) );
			$this->appendCondition("$heystack = $needles", $glue);
			return;
		}
		$values = '';
		$n_needles = count($needles);
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
	}

	public function appendConditionSomethingNotIn($heystack, $needles, $glue = 'AND') {
		$this->appendConditionSomethingIn($heystack, $needles, $glue, true); // See true
	}

	private static function appendInArray($values, & $array) {
		force_array($values);

		foreach($values as $value) {
			if( ! in_array($value, $array, true) ) {
				$array[] = $value;
			}
		}
	}

	public function getTables() {
		return $this->db->getTables( $this->tables );
	}

	public function getSelectFields() {
		return implode(', ', $this->selectFields);
	}

	public function getConditions() {
		return $this->conditions;
	}

	public function appendOrderBy($order_by) {
		if($this->orders !== null) {
			$this->orders .= ', ';
		}
		$this->orders .= $order_by;
	}

	public function getQuery() {
		$sql = "SELECT {$this->getSelectFields()} FROM {$this->getTables()}";
		if($this->conditions) {
			$sql .= " WHERE {$this->getConditions()}";
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

	public function query($query) {
		return $this->db->query( $this->getQuery($query) );
	}

	public function getResults($class_name = null, $params = [] ) {
		return $this->db->getResults( $this->getQuery(), $class_name, $params );
	}

	public function getRow($class_name = null, $params = []) {
		return $this->db->getRow( $this->getQuery(), $class_name, $params );
	}

	public function getValue($column_name) {
		return $this->db->getValue( $this->getQuery(), $column_name );
	}
}

