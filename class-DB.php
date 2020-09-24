<?php
# Copyright (C) 2015, 2018, 2019 Valerio Bozzolan
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

/*
 * Use: esc_html(), DEBUG, force_array()
 */

/**
 * A simple but effective database class!
 *
 * This class was forked from my defunct project called Open Student.
 */
class DB {

	/**
	 * MySQL connection link
	 */
	private $mysqli;

	/**
	 * Table prefix
	 *
	 * @var string
	 */
	private $prefix;

	/**
	 * Number of executed queries
	 *
	 * Read by get_num_queries()
	 *
	 * @var int
	 */
	public $queries = 0;

	/**
	 * Last query result
	 *
	 * @var query-result
	 */
	private $last = false;

	/**
	 * Singleton instance
	 *
	 * @var self
	 */
	private static $_instance;

	/**
	 * Get the singleton instance of this class
	 *
	 * @return self
	 */
	public static function instance() {
		if( ! self::$_instance ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Check if ::instance() was called
	 *
	 * @return boolean
	 */
	public static function instanced() {
		return isset( self::$_instance );
	}

	/**
	 * Constructor
	 *
	 * @param string $username Database username
	 * @param string $password Database password
	 * @param string $location Database location
	 * @param string $database Database name
	 * @param string $prefix   Table Prefix
	 * @param string $charset  Connection charset
	 */
	function __construct( $username = null, $password = null, $location = null, $database = null, $prefix = '', $charset = null ) {

		// default credentials usually defined from your load.php
		if( ! func_num_args() ) {
			$username = $GLOBALS['username'];
			$password = $GLOBALS['password'];
			$location = $GLOBALS['location'];
			$database = $GLOBALS['database'];
			$prefix   = $GLOBALS['prefix'];
		}

		$this->prefix = $prefix;

		// create database connection
		@$this->mysqli = new mysqli( $location, $username, $password, $database );
		if( $this->mysqli->connect_errno ) {
			$length = strlen( $password );
			throw new SucklessException( "unable to connect to the database '$database' using user '$username' and password ($length characters) on MySQL/MariaDB host '$location'" );
		}

		// eventually inherit default charset
		if( !$charset && isset( $GLOBALS['charset'] ) ) {
			$charset  = $GLOBALS['charset'];
		}

		// eventually set connection charset
		if( $charset ) {
			$this->mysqli->set_charset( $charset );
		}
	}

	/**
	 * Execute an SQL query
	 *
	 * @param string $query The SQL query to execute
	 */
	public function query( $query ) {
		$this->queries++;
		if( $this->last !== false && $this->last !== true ) {
			$this->last->free();
		}
		$this->last = $this->mysqli->query( $query );
		if( DEBUG_QUERIES ) {
			error( "query n. {$this->queries}: $query" );
		}
		if( !$this->last ) {
			throw new SucklessException( $this->getQueryErrorMessage( $query ) );
		}
		return $this->last;
	}

	/**
	 * Select only a row from the database
	 */
	public function getRow( $query, $class_name = null, $params = [] ) {
		$results = $this->getResults( $query, $class_name, $params );
		return isset( $results[ 0 ] ) ? $results[ 0 ] : null;
	}

	/**
	 * Select only a column from a single row
	 */
	public function getValue( $query, $column_name, $class_name = null, $params = [] ) {
		$row = $this->getRow( $query, $class_name, $params );
		return isset( $row->{ $column_name } ) ? $row->{ $column_name } : null;
	}

	/**
	 * Execute a query and return an array of $class_name objects.
	 *
	 * @param string $query Database SQL query.
	 * @param string $class_name The name of the class to instantiate.
	 * @param array $params Optional data for the $class_name constructor.
	 * @see http://php.net/manual/en/mysqli-result.fetch-object.php
	 */
	public function getResults( $query, $class_name = null, $params = [] ) {

		if( !$class_name ) {
			$class_name = 'Queried';
		}

		$this->query( $query );

		$res = [];
		while( $row = $this->last->fetch_object( $class_name, $params ) ) {
			$res[] = $row;
		}

		return $res;
	}

	/**
	 * Execute a query and return a generator of $class_name objects.
	 *
	 * @param string $query Database SQL query.
	 * @param string $class_name The name of the class to instantiate.
	 * @param array  $params Optional data for the $class_name constructor.
	 * @See http://php.net/manual/en/mysqli-result.fetch-object.php
	 */
	public function getGenerator( $query, $class_name = null, $params = [] ) {

		if( !$class_name ) {
			$class_name = 'Queried';
		}

		$result = $this->query( $query );
		$this->last = true; // to don't be killed from another query() call
		while( $row = $result->fetch_object( $class_name, $params ) ) {
			yield $row;
		}

		$result->free();
	}

	/**
	 * Run an INSERT query for a single row
	 *
	 * @param string  $table
	 * @param DBCol[] $cols
	 * @param array   $args arguments like 'replace-into
	 */
	public function insertRow( $table, $cols, $args = [] ) {

		$cols = DBCol::normalizeArray( $cols );

		// build column names
		$columns = [];
		foreach( $cols as $col ) {
			$columns[] = "`{$col->column}`";
		}

		// sanitize values
		$values = [];
		foreach( $cols as $col ) {
			$values[] = $this->forceType( $col->value, $col->forceType );
		}

		// default arguments
		$args = array_replace( [
			'replace-into' => false,
		], $args );

		$what = $args[ 'replace-into' ] ? 'REPLACE' : 'INSERT';

		$table = $this->getTable( $table, false );

		$columns_comma = implode( ', ', $columns );
		$values_comma  = implode( ', ', $values  );

		return $this->query("$what INTO $table ($columns_comma) VALUES ($values_comma)");
	}

	/**
	 * Executes one or multiple queries which are concatenated by a semicolon
	 *
	 * @param $queries string
	 */
	public function multiQuery( $queries ) {
		$i = 1;
		if( !$this->mysqli->multi_query( $queries ) ) {
			throw new SucklessException( "error in MySQLi#multi_query() with statement n. $i (starting from 1): {$this->mysqli->error}" );
		}
		while( $this->mysqli->more_results() ) {
			$i++;
			if( !$this->mysqli->next_result() ) {
				throw new SucklessException( "error in MySQLi#multi_query() with statement n. $i (starting from 1): {$this->mysqli->error}" );
			}
		}
	}

	/**
	 * Run an INSERT query for multiple rows
	 *
	 * @param string $table Table Name without prefix
	 * @param array $columns Assoc array of types ('ID' => 'null', 'name' => 's', ..)
	 * @param array $rows Array of rows (or just a row)
	 * @param array $args Extra arguments
	 */
	public function insert( $table, $columns, $rows, $args = [] ) {

		// default arguments
		$args = array_replace( [
			'replace-into' => false,
		], $args );

		// allow columns to be specified as an associative array
		$columns = DBCol::normalizeArray( $columns );

		// backticked column names
		$column_names = [];
		foreach( $columns as $column => $type ) {
			$column_names[] = "`$column`";
		}
		$columns_comma = implode( ', ', $column_names );

		// backward compatibility
		force_array( $rows );
		if( ! @is_array( $rows[0] ) ) {
			$rows = [$rows];
		}

		// just the types (in order to be indexed numerically)
		$types = array_values( $columns );
		$n_columns = count( $types );

		$value_groups = [];
		foreach( $rows as $i => $row ) {
			$query_values = [];

			if( $n_columns !== count( $row ) ) {
				throw new SucklessException( sprintf(
					"error using insert() in table %s: %d columns but %d values in row %d",
					$table,
					$n_columns,
					count( $row ),
					$i
				) );
			}

			$values_escaped = [];
			foreach( $types as $j => $type ) {
				$values_escaped[] = $this->forceType( $row[ $j ], $type );
			}

			$values_grouped = implode( ', ', $values_escaped );
			$value_groups[] = "($values_grouped)";
		}

		$value_groups_comma = implode( ', ', $value_groups );

		$action = $args['replace-into'] ? 'REPLACE' : 'INSERT';

		$table = $this->getTable( $table, false );

		return $this->query( "$action INTO $table ($columns_comma) VALUES $value_groups_comma" );
	}

	/**
	 * Run an UPDATE query
	 *
	 * @param string $table      Table name without prefix and backticks
	 * @param array  $columns    Array of DBCol(s)
	 * @param string $conditions Part after WHERE
	 * @param string $after
	 */
	public function update( $table, $columns, $conditions, $after = '' ) {
		$table = $this->getTable( $table, true );
		return $this->query( $this->buildUpdateQuery( $table, $columns, $conditions, $after ) );
	}

	/**
	 * Build an UPDATE query
	 *
	 * @param  string $table_raw  Table name with prefix and backticks
	 * @param  array  $columns    Array of DBCol[], or an associative array of column and its value
	 * @param  string $conditions part after WHERE
	 * @param  string $after
	 * @return string
	 */
	public function buildUpdateQuery( $table_raw, $columns, $conditions, $after = '' ) {

		// for backward compatibility allow a single column
		force_array( $columns );

		// allow columns to be specified as an associative array
		$columns = DBCol::normalizeArray( $columns );

		// build the value assignation for each column
		$sets = [];
		foreach( $columns as $column ) {
			$name  = $column->column;
			$value = $this->forceType( $column->value, $column->forceType );
			$sets[] = "`$name` = $value";
		}
		$sets_comma = implode( ', ', $sets );

		return "UPDATE $table_raw SET $sets_comma WHERE $conditions $after";
	}

	/**
	 * Get the last inserted ID
	 *
	 * @return string
	 */
	public function getLastInsertedID() {
		return $this->mysqli->insert_id;
	}

	/**
	 * Get the last number of rows retrieved
	 *
	 * @return int
	 */
	public function getLastNumRows() {
		return $this->last->num_rows;
	}

	/**
	 * Escape a string
	 *
	 * @param string $s String to be escaped
	 * @return string String escaped.
	 */
	public function escapeString( $s ) {
		return $this->mysqli->real_escape_string( $s );
	}

	/**
	 * Get the table prefix
	 *
	 * @return string
	 */
	public function getPrefix() {
		return $this->prefix;
	}

	/**
	 * Get table name with it's prefix (if any)
	 *
	 * @param string $name Table name
	 * @param mixed  $as   True to create an alias without the table prefix, or specify your alias
	 * @return string Table eventually with the alias
	 */
	public function getTable( $name, $as = true ) {
		$table = "`{$this->prefix}$name`";
		if( $as ) {
			if( $as === true ) {
				$as = $name;
			}
			$table .= " AS `$as`";
		}
		return $table;
	}

	/**
	 * Get the list of every table name inserted as arguments or as an array
	 *
	 * @return string
	 */
	public function getTables( $args = [] ) {
		$tables = [];
		if( ! is_array( $args ) ) {
			$args = func_get_args();
		}
		foreach( $args as $arg ) {
			$tables[] = $this->getTable( $arg, true );
		}
		return implode( ' JOIN ', $tables );
	}

	/**
	 * Force a string to a defined type
	 *
	 * @param string $s String to be forced.
	 * @param string $type Type ('d' for integer, 's' for string, 'f' for float, 'null' for autoincrement values or for "don't care" values).
	 * @see http://news.php.net/php.bugs/195815
	 * @return string Forced string
	 */
	public function forceType( $s, $type ) {

		if( $type === 'd' )
			return (int) $s; // Integer

		if( $type === 'f' )
			return (float) $s; // Float

		if( $type === 'dnull' )
			return ($s === null) ? 'NULL' : (int) $s; // Integer - or NULL

		if( $type === 'fnull' )
			return ($s === null) ? 'NULL' : (float) $s; // Float - or NULL

		if( $type === 's' )
			return "'{$this->escapeString($s)}'"; // String escaped

		if( $type === 'snull' )
			return ($s === null) ? 'NULL' : "'{$this->escapeString($s)}'"; // String escaped - or NULL

		if( $type === 'f' )
			return (float) $s; // Float value

		if( $type === '-' )
			return $s; // Float value

		if( $type === null || $type === 'null' )
			return 'NULL'; // 'NULL' literally for indexes

		error( "type $type unexpected in DB::forceType() and so it will be seen as 's'" );
		return $this->forceType( $s, 's' );
	}

	/**
	 * USE another database
	 *
	 * @param string $database
	 */
	public function selectDB( $database ) {
		$ok = $this->mysqli->select_db( $database );
		if( !$ok ) {
			throw new SucklessException( $this->getQueryErrorMessage( "USE $database" ) );
		}
	}

	/**
	 * Get the number of affected rows
	 *
	 * @return int
	 */
	public function affectedRows() {
		return $this->mysqli->affected_rows;
	}

	/**
	 * Please close the door when you leave a room
	 */
	public function closeConnection() {
		if( $this->mysqli ) {
			@$this->mysqli->close();
		}
	}

	/**
	 * Automatically close the door when you leave the room
	 */
	public function __destruct() {
		$this->closeConnection();
	}

	/**
	 * Show a friendly error about last MySQL query
	 *
	 * @param  string $query SQL query executed during the error
	 * @return string
	 */
	private function getQueryErrorMessage( $query ) {
		return sprintf(
			"error executing the query n. %d |%s| error: %s",
			$this->queries,
			$query,
			$this->mysqli->error
		);
	}

}
