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
		return $this;
	}

	public function uniqueTables() {
		$this->tables = array_unique($this->tables);
		return $this;
	}

	public function selectField($f) {
		self::appendInArray($f, $this->selectFields);
		return $this;
	}

	public function appendCondition($c, $glue = 'AND') {
		if($this->conditions !== null) {
			$this->conditions .= " $glue ";
		}
		$this->conditions .= $c;
		return $this;
	}

	public function setLimit($row_count, $offset = null) {
		$this->rowCount = $row_count;
		$this->offset = $offset;
		return $this;
	}

	public function appendConditionSomethingIn($heystack, $needles, $glue = 'AND', $not_in = false) {
		force_array($needles);

		$n_needles = count($needles);
		if( $n_needles === 1 ) {
			$symbol = ($not_in) ? '!=' : '=';
			$this->appendCondition(
				sprintf("$heystack $symbol '%s'", esc_sql( $needles[0] ) ),
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

	public function appendConditionSomethingNotIn($heystack, $needles, $glue = 'AND') {
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

	public function getTables() {
		return $this->db->getTables( $this->tables );
	}

	public function getSelectFields() {
		if( count( $this->selectFields ) === 0 ) {
			return '*';
		}
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
		return $this;
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

	public function query() {
		return $this->db->query( $this->getQuery() );
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

