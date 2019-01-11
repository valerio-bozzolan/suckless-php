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
 * Use: esc_html(), error_die(), DEBUG, force_array()
 */

/**
 * Database class
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
	 * @var int
	 */
	private $queries = 0;

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
	public function instance() {
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
	public function instanced() {
		return isset( self::$_instance );
	}

	/**
	 * Prepare the DB object.
	 *
	 * @param type $username Database username
	 * @param type $password Database password
	 * @param type $location Database location
	 * @param type $database Database name
	 * @param type $prefix Table Prefix
	 */
	function __construct( $username = null, $password = null, $location = null, $database = null, $prefix = '', $charset = 'utf8' ) {

		// default credentials usually defined from your load.php
		if( ! func_num_args() ) {
			$username = $GLOBALS['username'];
			$password = $GLOBALS['password'];
			$location = $GLOBALS['location'];
			$database = $GLOBALS['database'];
			$prefix   = $GLOBALS['prefix'];
		}

		$this->prefix = $prefix;

		@$this->mysqli = new mysqli( $location, $username, $password, $database );
		if( $this->mysqli->connect_errno ) {
			if( DEBUG ) {
				$length = strlen( $password );
				error_die( "unable to connect to the database '$database' using user '$username' and password ($length characters) on MySQL/MariaDB host '$location'" );
			} else {
				error_die( "error in establishing a database connection" );
			}
		} else {
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
		if( $this->last !== false && $this->last !== true) {
			$this->last->free();
		}
		$this->last = $this->mysqli->query( $query );
		if( !$this->last ) {
			error_die( $this->getQueryErrorMessage( $query ) );
		} elseif( DEBUG_QUERIES ) {
			error( "query n. {$this->queries}: $query" );
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
	 * @See http://php.net/manual/en/mysqli-result.fetch-object.php
	 */
	public function getResults($query, $class_name = null, $params = [] ) {
		// IS_ARRAY() IS SHIT FOR HISTORICAL REASONS
		if( $class_name === null || is_array( $class_name ) ) {
			$class_name = 'Queried';
		}
		// IS_ARRAY() IS SHIT FOR HISTORICAL REASONS

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
	 * @param array $params Optional data for the $class_name constructor.
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
	 * To insert a single row.
	 * I have not time to check if $dbCols are DBCol objects.
	 *
	 * @param string $table_name
	 * @param DBCol[] $cols
	 * @param $args arguments like 'replace-into
	 */
	public function insertRow( $table_name, $cols, $args = [] ) {
		$SQL_columns = '';
		$n = count($cols);
		for($i=0; $i<$n; $i++) {
			if($i !== 0) {
				$SQL_columns .= ', ';
			}
			$SQL_columns .= "`{$cols[$i]->column}`";
		}

		$values = '';
		for($i=0; $i<$n; $i++) {
			if($i !== 0) {
				$values .= ', ';
			}
			$values .= $this->forceType($cols[$i]->value, $cols[$i]->forceType);
		}

		$args = array_replace( [
			'replace-into' => false,
		], $args );

		$what = $args[ 'replace-into' ] ? 'REPLACE' : 'INSERT';

		return $this->query("$what INTO {$this->getTable($table_name)} ($SQL_columns) VALUES ($values)");
	}

	/**
	 * Executes one or multiple queries which are concatenated by a semicolon
	 *
	 * @param $queries string
	 */
	public function multiQuery( $queries ) {
		return $this->mysqli->multi_query( $queries );
	}

	/**
	 * To execute clean insert SQL queries.
	 *
	 * @param string $table_name Table Name without prefix
	 * @param array $columns Assoc array of types ('ID' => 'null', 'name' => 's', ..)
	 * @param array $rows Array of rows
	 * @param array $args Extra arguments
	 */
	public function insert($table_name, $columns, $rows, $args = []) {
		$args = array_replace( [
			'replace-into' => false
		], $args );

		force_array($rows);

		if( ! @is_array($rows[0]) ) {
			$rows = [ $rows ]; // array_columns('value col 1', '..') => array_rows( array_columns( 'value col 1', ..) )
		}

		$n_columns = count($columns);
		$n_rows = count($rows);

		$SQL_columns = '';

		$first = key($columns);
		foreach($columns as $column => $type) {
			if($column !== $first) {
				$SQL_columns .= ', ';
			}
			$SQL_columns .= "`$column`";
		}

		$insert = ($args['replace-into']) ? 'REPLACE' : 'INSERT';

		$SQL = "$insert INTO {$this->getTable($table_name)} ($SQL_columns) VALUES";
		for($i=0; $i<$n_rows; $i++) {
			$SQL .= ($i === 0) ? ' (' : ', (';
			$SQL_values = [];

			if($n_columns != count($rows[$i])) {
				error( sprintf(
					__("Errore inserendo nella tabella <em>%s</em>. Colonne: <em>%d</em>. Colonne values[<em>%d</em>]: <em>%d</em>"),
					esc_html($table_name),
					$n_columns,
					$i,
					count($rows[$i])
				) );
				return false;
			}

			$j = 0;
			foreach($columns as $column => $type) {
				$SQL_values[] = $this->forceType($rows[$i][$j], $type);
				$j++;
			}

			$SQL .= implode(', ', $SQL_values) . ')';
		}
		return $this->query($SQL);
	}

	/**
	 * To execute update queries.
	 */
	public function update($table_name, $dbCols, $conditions, $after = '') {
		force_array($dbCols);

		$SQL = "UPDATE {$this->getTable($table_name)} SET ";
		$n_cols = count($dbCols);
		for($i=0; $i<$n_cols; $i++) {
			if($i !== 0) {
				$SQL .= ', ';
			}
			$val = $this->forceType($dbCols[$i]->value, $dbCols[$i]->forceType);
			$SQL .= "`{$dbCols[$i]->column}` = $val";
		}
		if($after !== '') {
			$after = " $after";
		}
		$SQL .= " WHERE {$conditions}{$after}";
		return $this->query($SQL);
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
	 * Escape a string
	 *
	 * @param string $s String to be escaped
	 * @return string String escaped.
	 */
	public function escapeString($s) {
		return $this->mysqli->real_escape_string($s);
	}

	public function getqueries() {
		return $this->queries;
	}

	public function getPrefix() {
		return $this->prefix;
	}

	/**
	 * Return a table name with it's school prefix
	 *
	 * @param string $name Table name
	 * @param boolean $as True if you want to access to the table $name in the SQL
	 * @return string Table $name with the prefix
	 */
	public function getTable($name, $as = false) {
		if($this->prefix === '') {
			$as = false;
		}
		$r = "`{$this->prefix}$name`";
		if($as) {
			$r .= " AS `$name`";
		}
		return $r;
	}

	/**
	 * Return the list of every table name inserted as arguments or as an []
	 */
	public function getTables($args = []) {
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
	 * Force a string to a defined type.
	 *
	 * @param string $s String to be forced.
	 * @param string $type Type ('d' for integer, 's' for string, 'f' for float, 'null' for autoincrement values or for "don't care" values).
	 * @see http://news.php.net/php.bugs/195815
	 * @return string Forced string
	 */
	private function forceType($s, $type) {

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

		error( sprintf(
			"Tipo '%s' non concesso in DB::forceType(). Vedi la documentazione (esiste?). Sarà usato l'escape 's'.",
			esc_html($type)
		) );

		return $this->forceType($s, 's');
	}

	/**
	 * Get the number of affected rows
	 *
	 * @return int
	 */
	public function affectedRows() {
		$this->mysqli->affected_rows();
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
	 * Used to show "friendly" error.
	 *
	 * @param string $SQL SQL query executed during the error.
	 * @return string Kind message.
	 */
	private function getQueryErrorMessage($SQL) {
		return sprintf(
			__("Errore eseguendo una query SQL: Query n. %d: <blockquote><pre>%s</pre></blockquote><br />Errore: <pre>%s</pre>"),
			$this->queries,
			$SQL,
			esc_html($this->mysqli->error)
		);
	}

}
