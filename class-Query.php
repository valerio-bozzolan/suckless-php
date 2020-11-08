<?php
# Copyright (C) 2017, 2018, 2019, 2020 Valerio Bozzolan
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

/**
 * Class useful to build a database query
 */
class Query {

	/**
	 * Connection to the database
	 *
	 * @var DB
	 */
	private $db;

	/**
	 * Class used as default for the results
	 *
	 * @var string
	 */
	protected $resultClass;

	/**
	 * Fields for the SELECT
	 *
	 * @var array
	 */
	private $selectFields = [];

	/**
	 * Table names (without table prefix)
	 *
	 * @var array
	 */
	private $tables = [];

	/**
	 * Stuff to build the FROM (custom sub-queries, etc.)
	 *
	 * @var array
	 */
	private $from = [];

	/**
	 * "GROUP BY" statements
	 *
	 * @var array
	 */
	private $groups = [];

	/**
	 * "HAVING" statements
	 *
	 * @var array
	 */
	private $having;

	/**
	 * "WHERE" conditions
	 *
	 * @var string
	 */
	private $conditions = '';

	/**
	 * "ORDER BY" statements
	 *
	 * @var string
	 */
	private $orders;

	/**
	 * Offset for the LIMIT
	 *
	 * @var int
	 */
	private $offset;

	/**
	 * Row count for the LIMIT
	 *
	 * @var int
	 */
	private $rowCount;

	/**
	 * Default glue for the conditions
	 *
	 * @var string
	 */
	private $glue = 'AND';

	/**
	 * Flag that indicates if the next condition statements needs a glue
	 *
	 * @var boolean
	 */
	private $needsGlue = false;

	/**
	 * Constructor
	 *
	 * @param object $db         Database object (class DB) or NULL for the default one
	 * @param string $class_name Class to encapsulate the database result
	 */
	public function __construct( $db = null, $class_name = null ) {

		// assign the specified database or the default one
		$this->db = $db ? $db : DB::instance();

		if( $class_name ) {
			$this->defaultClass( $class_name );
		}
	}

	/**
	 * Constructor shortcut from class name
	 *
	 * @param string $class_name Class to encapsulate the database result
	 * @param object $db         Database object (class DB)
	 * @return self
	 */
	public static function factory( $class_name = null, $db = null ) {
		return new self( $db, $class_name );
	}

	/**
	 * Selected fields (SELECT).
	 *
	 * @param string|array $fields
	 * @return self
	 */
	public function select() {
		return $this->appendInArray( func_get_args(), $this->selectFields );
	}

	/**
	 * Selected fields eventually with an AS field
	 *
	 * @param string $select Select field
	 * @param string $as     AS column name
	 * @return self
	 */
	public function selectAs( $select, $as = null ) {
		if( $as ) {
			$as = " $as";
		}
		return $this->select( "( $select )$as" );
	}

	/**
	 * Select an EXISTS subquery
	 *
	 * @param Query   $query
	 * @param string  $alias Result alias
	 * @param boolean $positive Set to false to have a "NOT EXISTS"
	 * @return self
	 */
	public function selectExists( $query, $alias = false, $positive = true ) {
		$query_txt = $query->getQuery();
		$query_txt = "EXISTS( $query_txt )";
		if( !$positive ) {
			$query_txt = "NOT $query_txt";
		}
		return $this->selectAs( $query_txt, $alias );
	}

	/**
	 * Select an EXISTS subquery
	 *
	 * @param Query   $query
	 * @param string  $alias Result alias
	 * @return self
	 */
	public function selectNotExists( $query, $alias = false ) {
		return $this->selectExists( $query, $alias, false );
	}

	/**
	 * Selected tables
	 *
	 * @param string|array $tables Table/tables without database prefix
	 * @return self
	 */
	public function from() {
		return $this->appendInArray( func_get_args(), $this->tables );
	}

	/**
	 * Select a table with a custom alias
	 *
	 * @param  string $from  Table name to be used in the FROM (without table prefix)
	 * @param  string $alias Your table alias (the "AS something" part)
	 * @return self
	 */
	public function fromAlias( $from, $alias ) {
		$this->from[] = $this->db->getTable( $from, $alias );
		return $this;
	}

	/**
	 * Set a custom from value
	 *
	 * @param string e.g. "(SELECT * ...) as t1"
	 * @return self
	 */
	public function fromCustom( $from ) {
		$this->from[] = $from;
		return $this;
	}

	/**
	 * To compare two columns somehow
	 *
	 * @param string $one  Something (NOT SANITIZED)
	 * @param string $verb Comparison method
	 * @param string $two  Something (NOT SANITIZED)
	 * @param string $glue Conditions glue
	 * @return self
	 */
	public function compare( $one, $verb, $two, $glue = null ) {
		return $this->where( "$one $verb $two", $glue );
	}

	/**
	 * To be used for PRIMARY KEY joins
	 *
	 * @param string $one  Something (NOT SANITIZED)
	 * @param string $two  Something (NOT SANITIZED)
	 * @param string $glue Conditions glue
	 * @return self
	 */
	public function equals( $one, $two, $glue = null ) {
		return $this->compare( $one, '=', $two, $glue );
	}

	/**
	 * Append a query condition
	 *
	 * @param string $condition Something as 'field = 1'
	 * @param string $glue      Conditions glue
	 * @return self
	 */
	public function where( $condition, $glue = null ) {
		if( $this->needsGlue ) {
			if( !$glue ) {
				$glue = $this->glue;
			}
			$this->conditions .= " $glue ";
		}
		$this->conditions .= $condition;
		$this->needsGlue = true;
		return $this;
	}

	/**
	 * Intended to compare a column with a number
	 *
	 * @param  string $one   Column name
	 * @param  int    $value Value
	 * @param  string $verb  Compare method
	 * @param  string $glue  Condition glue
	 * @return self
	 */
	public function whereInt( $column, $value, $verb = '=', $glue = null ) {
		return $this->compare( $column, $verb, (int)$value, $glue );
	}

	/**
	 * Intended to compare a column with a string
	 *
	 * @param  string $one   Column name
	 * @param  string $value Value
	 * @param  string $verb  Compare method
	 * @param  string $glue  Condition glue
	 * @return self
	 */
	public function whereStr( $column, $value, $verb = '=', $glue = null ) {
		$value = esc_sql( $value );
		return $this->compare( $column, $verb, "'$value'", $glue );
	}

	/**
	 * Filter by a LIKE command
	 *
	 * @param $column string Column name
	 * @param $value string Value to be liked
	 * @param $left string To have whatever before the value
	 * @param $right string To have whatever after the value
	 */
	public function whereLike( $column, $value, $left = true, $right = true ) {
		$left  = $left  ? '%' : '';
		$right = $right ? '%' : '';
		$value = $left . esc_sql_like( $value ) . $right;
		return $this->whereStr( $column, $value, 'LIKE' );
	}

	/**
	 * Append a custom join from the latest selected table
	 *
	 * @param $type  string Join type e.g. INNER, LEFT, RIGHT, etc.
	 * @param $table string Table name
	 * @param $a     string First column name for the ON clause (or complete ON condition if $b is empty)
	 * @param $b     string Second column name for the ON clause (or complete ON condition if $a is empty)
	 * @param $alias mixed  Table alias. As default is true, and the table prefix is removed.
	 */
	public function joinOn( $type, $table, $a, $b = null, $alias = true ) {
		if( $this->from ) {
			$previous = array_pop( $this->from );
		} elseif( $this->tables ) {
			$previous = array_pop( $this->tables );
			$previous = $this->db->getTable( $previous, true );
		} else {
			throw new InvalidArgumentException( 'not enough tables' );
		}
		$table = $this->db->getTable( $table, $alias );

		$conditions = [];
		if( $a ) {
			$conditions[] = $a;
		}
		if( $b ) {
			$conditions[] = $b;
		}
		$on = implode( '=', $conditions );

		$this->from[] = "$previous $type JOIN $table ON ($on)";
		return $this;
	}

	/**
	 * Group by
	 *
	 * @param string|array $groups Group by
	 * @return self
	 */
	public function groupBy() {
		return $this->appendInArray( func_get_args(), $this->groups );
	}

	/**
	 * You have double-selected a table but that wasn't your goal.
	 *
	 * @return self
	 */
	public function uniqueTables() {
		$this->tables = array_unique( $this->tables );
		return $this;
	}

	/**
	 * LIMIT count, offset.
	 *
	 * @param int $row_count Max numbers of elements.
	 * @param int $offset Starting offset
	 * @return self
	 */
	public function limit( $row_count, $offset = null ) {
		$this->rowCount = $row_count;
		$this->offset = $offset;
		return $this;
	}

	/**
	 * Get the LIMIT SQL clause (if any)
	 *
	 * @return string
	 */
	public function getLimitClause() {
		if( $this->rowCount !== null ) {
			$query = ' LIMIT ';
			if( $this->offset ) {
				$query .= "{$this->offset}, ";
			}
			return $query . $this->rowCount;
		}
	}

	/**
	 * Handy shortcut for `something IN (values)` condition.
	 *
	 * Actually the type (int or string) is inherited from the first value.
	 *
	 * @param string       $heystack Column name
	 * @param string|array $needles  Values to compare
	 * @return self
	 */
	public function whereSomethingIn( $heystack, $needles, $glue = null, $not_in = false ) {
		force_array( $needles );

		// no needles no filter
		$n_needles = count( $needles );
		if( !$n_needles ) {
			return $this;
		}

		// the type is inherited from the first value
		$is_int = is_int( reset( $needles ) );

		// case with just one element
		if( $n_needles === 1 ) {
			$needle = array_pop( $needles );
			$verb = $not_in ? '!=' : '=';
			return $is_int
				? $this->whereInt( $heystack, $needle, $verb )
				: $this->whereStr( $heystack, $needle, $verb );
		}

		// collect the series
		$escaped_needles = [];
		$force_type = $is_int ? 'd' : 's';
		foreach( $needles as $needle ) {
			$escaped_needles[] = $this->db->forceType( $needle, $force_type );
		}

		// build the condition
		$values = implode( ', ', $escaped_needles );
		$values = "($values)";
		$verb = $not_in ? 'NOT IN' : 'IN';
		return $this->compare( $heystack, $verb, $values, $glue );
	}

	/**
	 * Handy shortcut for `something NOT IN (values)` condition.
	 *
	 * @param string $heystack Field.
	 * @param string|array $needles Values to compare.
	 */
	public function whereSomethingNotIn( $heystack, $needles, $glue = null ) {
		return $this->whereSomethingIn( $heystack, $needles, $glue, true ); // See true
	}

	/**
	 * Set the default glue
	 *
	 * @param string $glue
	 * @return self
	 */
	public function setGlue( $glue ) {
		$this->glue = $glue;
		return $this;
	}

	/**
	 * Open a bracket
	 *
	 * @param  string $glue Condition glue
	 * @return self
	 */
	public function openBracket( $glue = null ) {
		$this->where( '(', $glue );
		$this->needsGlue = false;
		return $this;
	}

	/**
	 * Close a bracket
	 *
	 * @return self
	 */
	public function closeBracket() {
		$this->conditions .= ')';
		$this->needsGlue = true;
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
				$tables .= ' JOIN ';
			}
			$tables .= implode( ' JOIN ', $this->from );
		}
		return $tables;
	}

	/**
	 * Get the SELECT clause
	 *
	 * @return string
	 */
	public function getSelect() {
		if( $this->selectFields ) {
			return implode( ', ', $this->selectFields );
		}
		return '*';
	}

	/**
	 * Get the WHERE clause body
	 *
	 * @return string
	 */
	public function getWhere() {
		return $this->conditions;
	}

	/**
	 * Get the GROUP BY clause body
	 *
	 * @return string
	 */
	public function getGroupBy() {
		return implode(', ', $this->groups);
	}

	/**
	 * Set the HAVING clause
	 *
	 * @param string $having
	 * @return self
	 */
	public function having( $having ) {
		$this->having = $having;
		return $this;
	}

	/**
	 * Order by a field
	 *
	 * @param string $order_by Field to sort
	 * @param string $how      Choose 'DESC' or 'ASC', failing to ASC
	 * @return self
	 */
	public function orderBy( $order_by, $how = null ) {
		if( null !== $this->orders ) {
			$this->orders .= ', ';
		}
		$this->orders .= $order_by;
		if( $how ) {
			$this->orders .= ' ' . self::filterDirection( $how );
		}
		return $this;
	}

	/**
	 * Mark a SELECT query as needed for an UPDATE
	 *
	 * «If you use FOR UPDATE with a storage engine that uses page or row locks,
	 *  rows examined by the query are write-locked until the end of the current transaction.»
	 *
	 * @return self
	 */
	public function forUpdate() {
		$this->forUpdate = true;
		return $this;
	}

	/**
	 * Build an SQL SELECT query
	 *
	 * @return string SQL query
	 * @see https://dev.mysql.com/doc/refman/8.0/en/select.html
	 */
	public function getQuery() {
		$sql = "SELECT {$this->getSelect()}";

		$from = $this->getFrom();
		if( $from ) {
			$sql .= " FROM $from";
		}

		if( $this->conditions ) {
			$sql .= " WHERE {$this->conditions}";
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
		$sql .= $this->getLimitClause();
		if( isset( $this->forUpdate ) ) {
			$sql .= " FOR UPDATE";
		}
		return $sql;
	}

	/**
	 * Where a subquery exists
	 *
	 * @param $query object  Query
	 * @param $not   boolean Set to FALSE to have a NOT EXISTS
	 * @return self
	 */
	public function whereExists( $query, $exists = true ) {
		$verb = $exists ? 'EXISTS' : 'NOT EXISTS';
		$query_raw = $query->getQuery();
		return $this->where( "$verb ($query_raw)" );
	}

	/**
	 * Where a subquery not exists
	 *
	 * @param $query object Query
	 * @return self
	 */
	public function whereNotExists( $query ) {
		return $this->whereExists( $query, false );
	}

	/**
	 * Get a DELETE query
	 *
	 * Note that you MUST specify a condition.
	 *
	 * Note that the SQL DELETE query has a strange syntax for
	 * table aliases. See https://stackoverflow.com/a/11005244
	 *
	 * @return string SQL DELETE query
	 */
	public function getDeleteQuery() {
		$table = reset( $this->tables ); // just the only one
		$table_full = $this->db->getTable( $table, true );
		return "DELETE `$table` FROM $table_full WHERE {$this->conditions} {$this->getLimitClause()}";
	}

	/**
	 * Run an SQL DELETE query
	 *
	 * @see https://dev.mysql.com/doc/refman/8.0/en/delete.html
	 */
	public function delete() {
		return $this->runDangerousQuery( $this->getDeleteQuery() );
	}

	/**
	 * Build and run an SQL UPDATE query
	 *
	 * Note that you MUST specify a condition.
	 *
	 * @param  array $columns Array of DBCol[]
	 * @return array
	 */
	public function update( $columns ) {
		$query = $this->db->buildUpdateQuery( $this->getFrom(), $columns, $this->conditions, $this->getLimitClause() );
		return $this->runDangerousQuery( $query );
	}

	/**
	 * Run an INSERT query
	 *
	 * @param array $data Array of DBCol sanitized data
	 * @param array $args Associative array of arguments
	 *                      'replace-into' boolean Run this query as a REPLACE INTO
	 */
	public function insertRow( $data, $args = [] ) {
		if( empty( $this->tables[0] ) ) {
			throw new SucklessException( "cannot insert without a table" );
		}
		return $this->db->insertRow( $this->tables[0], $data, $args );
	}

	/**
	 * Set the default class to incapsulate the result set
	 *
	 * @param string $class_name Class name
	 * @return self
	 */
	public function defaultClass( $class_name ) {
		$this->resultClass = $class_name;
		return $this;
	}

	/**
	 * Get the specified class name or the default one
	 *
	 * @param  string $class_name
	 * @return string
	 */
	public function getDefaultClass( $class_name = null ) {
		return $class_name ? $class_name : $this->resultClass;
	}

	/**
	 * Run the SELECT query
	 *
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
	 *
	 * @param string $column_name
	 * @see DB#getValue()
	 * @return mixed
	 */
	public function queryValue( $column_name ) {
		return $this->db->getValue( $this->getQuery(), $column_name );
	}

	/**
	 * Append a value or some values into an array
	 *
	 * @param  array|string $values An array of values or just a value
	 * @param  array        $array  Array that will be modified
	 * @return self
	 */
	private function appendInArray( $values, & $array ) {
		// retro-compatibility feature
		if( isset( $values[ 0 ] ) && is_array( $values[ 0 ] ) ) {
			$values = $values[ 0 ];
		}
		foreach( $values as $value ) {
			$array[] = $value;
		}
		return $this;
	}

	/**
	 * Filter a direction
	 *
	 * The fallback is ASC.
	 *
	 * @param $dir string
	 * @return string DESC|ASC
	 */
	public static function filterDirection( $dir ) {
		$dir = strtoupper( $dir );
		return $dir === 'DESC' ? 'DESC' : 'ASC';
	}

	/**
	 * Check if this query is involving just a single table
	 *
	 * @return bool
	 */
	private function isSimpleFrom() {
		return count( $this->from ) + count( $this->tables ) === 1;
	}

	/**
	 * Run a query that MUST involve just a single table and have a condition
	 *
	 * @param string $query SQL query
	 * @param string $kind  Kind of query (just for the debug message)
	 */
	private function runDangerousQuery( $query ) {
		if( !$this->conditions || !$this->isSimpleFrom() ) {
			if( DEBUG_QUERIES ) {
				error( $query );
			}
			if( $this->conditions ) {
				throw new SucklessException( "for security reasons you cannot build this kind of query involving multiple tables" );
			}
			throw new SucklessException( "for security reasons you cannot build this kind of query without a condition" );
		}
		return $this->db->query( $query );
	}
}
