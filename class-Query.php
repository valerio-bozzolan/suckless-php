<?php
# Copyright (C) 2017, 2018 Valerio Bozzolan
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
	private $from = [];
	private $groups = [];
	private $having;
	private $conditions;
	private $offset;
	private $rowCount;
	private $orders;

	public function __construct( &$db = null ) {
		$this->db = $db ? $db : expect('db'); // Dipendency injection
	}

	/**
	 * Construct shortcut.
	 *
	 * @param string $class_name Class to encapsulate the database result
	 * @return Query
	 */
	public static function factory( $class_name = null ) {
		$t = new self();
		$class_name and $t->defaultClass( $class_name );
		return $t;
	}


	/**
	 * Selected fields (SELECT).
	 *
	 * @param string|array $fields
	 * @return Query
	 */
	public function select() {
		return $this->appendInArray( func_get_args() , $this->selectFields );
	}

	/**
	 * Selected tables without prefix.
	 *
	 * @param string|array $tables Table/tables
	 * @return Query
	 */
	public function from() {
		return $this->appendInArray( func_get_args(), $this->tables );
	}

	/**
	 * Set a custom from value
	 *
	 * @param string e.g. "(SELECT * ...) as t1"
	 * @return Query
	 */
	public function fromCustom( $from ) {
		$this->from[] = $from;
		return $this;
	}

	/**
	 * Group by
	 * @param string|array $groups Group by
	 * @return Query
	 */
	public function groupBy() {
		return $this->appendInArray( func_get_args(), $this->groups );
	}

	/**
	 * You have double-selected a table but that wasn't your goal.
	 *
	 * @return Query
	 */
	public function uniqueTables() {
		$this->tables = array_unique( $this->tables );
		return $this;
	}

	/**
	 * Query condition.
	 *
	 * @param string $condition Something as 'field = 1'
	 * @return Query
	 */
	public function where( $condition, $glue = 'AND' ) {
		if( null !== $this->conditions ) {
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
	public function equals( $one, $two ) {
		return $this->where("$one = $two");
	}

	/**
	 * Intended to compare a property with a number.
	 *
	 * @param string $one Column name
	 * @param int $value Value
	 */
	public function whereInt( $column, $value ) {
		return $this->equals( $column, (int)$value );
	}

	/**
	 * Intended to compare a property with a string.
	 *
	 * @param string $one Column name
	 * @param string $value Value
	 */
	public function whereStr( $column, $value ) {
		$value = esc_sql( $value );
		return $this->equals( $column, "'$value'" );
	}

	/**
	 * LIMIT count, offset.
	 *
	 * @param int $row_count Max numbers of elements.
	 * @param int $offset Starting offset
	 * @return Query
	 */
	public function limit($row_count, $offset = null) {
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
	public function whereSomethingIn( $heystack, $needles, $glue = 'AND', $not_in = false ) {
		force_array($needles);

		$n_needles = count($needles);
		if( $n_needles === 1 ) {
			$needle = array_pop( $needles );
			$this->where(
				sprintf("%s %s '%s'",
					$heystack,
					$not_in ? '!=' : '=',
					esc_sql( $needle )
				),
				$glue
			);
			return $this;
		}

		$escaped_needles = [];
		foreach( $needles as $needle ) {
			$escaped_needles[] = single_quotes( esc_sql( $needle ) );
		}

		if( $escaped_needles ) {
			$values = implode(', ', $escaped_needles);
			if($not_in) {
				$this->where("$heystack NOT IN ($values)", $glue);
			} else {
				$this->where("$heystack IN ($values)", $glue);
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
	public function whereSomethingNotIn( $heystack, $needles, $glue = 'AND' ) {
		$this->appendConditionSomethingIn($heystack, $needles, $glue, true); // See true
		return $this;
	}

	/**
	 * Get the FROM clause
	 *
	 * @return string
	 */
	public function getFrom() {
		$tables = $this->db->getTables( $this->tables );
		if( $this->from ) {
			if( $tables ) {
				$tables .= ', ';
			}
			$tables .= implode( ', ', $this->from );
		}
		return $tables;
	}

	public function getSelect() {
		if( count( $this->selectFields ) === 0 ) {
			return '*';
		}
		return implode(', ', $this->selectFields);
	}

	public function getWhere() {
		return $this->conditions;
	}

	public function getGroupBy() {
		return implode(', ', $this->groups);
	}

	public function having( $having ) {
		$this->having = $having;
		return $this;
	}

	/**
	 * Order by
	 *
	 * @param $order_by string Field to sort
	 * @param $how string DESC|ASC, failing to ASC
	 * @return self
	 */
	public function orderBy( $order_by, $how = null ) {
		if( null !== $this->orders ) {
			$this->orders .= ', ';
		}
		$this->orders .= $order_by;
		if( $how ) {
			$this->orders .= $how === 'DESC' ? ' DESC' : ' ASC';
		}
		return $this;
	}

	/**
	 * @return string SQL query
	 */
	public function getQuery() {
		$sql = "SELECT {$this->getSelect()} FROM {$this->getFrom()}";
		if( $this->conditions ) {
			$sql .= " WHERE {$this->getWhere()}";
		}
		if( $this->groups ) {
			$sql .= " GROUP BY {$this->getGroupBy()}";
		}
		if( $this->having ) {
			$sql .= " HAVING {$this->having}";
		}
		if( $this->orders ) {
			$sql .= " ORDER BY {$this->orders}";
		}
		if( null !== $this->rowCount ) {
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
	public function defaultClass( $class_name ) {
		$this->class_name = $class_name;
		return $this;
	}

	public function getDefaultClass( $default_class = null ) {
		if( null === $this->class_name ) {
			return $default_class;
		}
		return $this->class_name;
	}

	/**
	 * @see DB#query()
	 */
	public function query() {
		return $this->db->query( $this->getQuery() );
	}

	/**
	 * Get the array of result sets encapsulated in the specified class.
	 *
	 * @param string $class_name
	 * @see DB#getResults()
	 * @return array
	 */
	public function queryResults( $class_name = null, $params = [] ) {
		return $this->db->getResults(
			$this->getQuery(),
			$this->getDefaultClass( $class_name ),
			$params
		);
	}

	/**
	 * Get a generator of $class_name objects.
	 *
	 * @param string $class_name
	 * @see DB#getResults()
	 * @return array
	 */
	public function queryGenerator( $class_name = null, $params = [] ) {
		return $this->db->getGenerator(
			$this->getQuery(),
			$this->getDefaultClass( $class_name ),
			$params
		);
	}

	/**
	 * Get the result set encapsulated in the specified class.
	 *
	 * @param string $class_name
	 * @see DB#getRow()
	 * @return null|Object
	 */
	public function queryRow( $class_name = null, $params = [] ) {
		return $this->db->getRow(
			$this->getQuery(),
			$this->getDefaultClass( $class_name ),
			$params
		);
	}

	/**
	 * Get the specified column from the first result set.
	 * @param string $column_name
	 * @see DB#getValue()
	 * @return mixed
	 */
	public function queryValue( $column_name ) {
		return $this->db->getValue( $this->getQuery(), $column_name );
	}

	private function appendInArray( $values, & $array ) {
		// Retrocompatibility patch
		if( isset( $values[0] ) && is_array( $values[0] ) ) {
			$values = $values[0];
		}

		foreach( $values as $value ) {
			if( $value && ! in_array( $value, $array, true ) ) {
				$array[] = $value;
			}
		}
		return $this;
	}
}
