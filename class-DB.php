<?php
# Copyright (C) 2015, 2018 Valerio Bozzolan
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
 * The database class from the Open Student project.
 * Some functions to run MySQL queries and analyze results.
 */
class DB {

	/**
	 * MySQL connection link.
	 * @var MySQLi->link MySQLi connection link
	 */
	private $mysqli;

	/**
	 * Table prefix.
	 * @var string
	 */
	private $prefix;

	/**
	 * Number of executed queries.
	 * @var int
	 */
	private $numQueries = 0;

	/**
	 * Last query result.
	 * @var query-result
	 */
	private $lastResult = false;

	/**
	 * Prepare the DB object.
	 *
	 * @param type $username Database username
	 * @param type $password Database password
	 * @param type $location Database location
	 * @param type $database Database name
	 * @param type $prefix Table Prefix
	 */
	function __construct($username = null, $password = null, $location = null, $database = null, $prefix = '', $charset = 'utf8') {
		if( func_num_args() === 0 ) {
			$username  = @$GLOBALS['username'];
			$password  = @$GLOBALS['password'];
			$location  = @$GLOBALS['location'];
			$database  = @$GLOBALS['database'];
			$prefix    = @$GLOBALS['prefix'];
		}

		$this->prefix = $prefix;

		@$this->mysqli = new mysqli($location, $username, $password, $database);
		if( $this->errorConnection() ) {
			if(DEBUG) {
				$password_shown = ($password === '') ? __("nessuna") : sprintf(
					__("di %d caratteri"),
					strlen( $password )
				);

				error_die( sprintf(
					__("Impossibile connettersi al database '%s' tramite l'utente '%s' e password (%s) sul server MySQL/MariaDB '%s'. Specifica correttamente queste informazioni nel file di configurazione del tuo progetto (usualmente '%s'). %s."),
					$database,
					$username,
					$password_shown,
					$location,
					'load.php',
					HTML::a(
						'https://github.com/valerio-bozzolan/boz-php-another-php-framework/blob/master/README.md#use-it',
						__("Documentazione")
					)
				) );
			} else {
				error_die( __("Errore nello stabilire una connessione al database.") );
			}
		}

		@$this->mysqli->set_charset($charset);
	}

	function __destruct() {
		$this->closeConnection();
	}

	/**
	 * Lock the door when you leave a room.
	 */
	public function closeConnection() {
		$this->mysqli && @$this->mysqli->close();
	}

	/**
	 * To execute a query.
	 *
	 * @param string $SQL The SQL query to execute
	 * @param boolean $tagReplace Use tag sobstitution, or no
	 */
	public function query($query) {
		$this->numQueries++;
		if( $this->lastResult !== false && $this->lastResult !== true) {
			$this->lastResult->free();
		}
		$this->lastResult = $this->mysqli->query($query);
		if( ! $this->lastResult ) {
			error_die( $this->getQueryErrorMessage($query) );
		} elseif(DEBUG && SHOW_EVERY_SQL) {
			$this->showSQL($query);
		}
		return $this->lastResult;
	}

	private function showSQL($SQL) {
		echo "<p>" . sprintf(
			__("Query numero %d: <pre>%s</pre>"),
			$this->numQueries,
			$SQL
		) . "</p>\n";
	}

	/**
	 * Select only a row from the database
	 */
	public function getRow($query, $class_name = null, $params = [] ) {
		$results = $this->getResults($query, $class_name, $params);
		return @$results[0];
	}

	/**
	 * Select only a column from a single row
	 */
	public function getValue($query, $column_name, $class_name = null, $params = [] ) {
		$row = $this->getRow($query, $class_name, $params);
		return @$row->{$column_name};
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

		$this->query($query);

		$res = [];
		while( $row = $this->lastResult->fetch_object($class_name, $params) ) {
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
		if( null === $class_name ) {
			$class_name = 'Queried';
		}
		$result = $this->query( $query );
		$this->lastResult = true; // to don't be killed from another query() call
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
	 */
	public function insertRow($table_name, $cols) {
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
		return $this->query("INSERT INTO {$this->getTable($table_name)} ($SQL_columns) VALUES ($values)");
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
				DEBUG && error( sprintf(
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

	public function getNumQueries() {
		return $this->numQueries;
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

		DEBUG && error( sprintf(
			"Tipo '%s' non concesso in DB::forceType(). Vedi la documentazione (esiste?). Sarà usato l'escape 's'.",
			esc_html($type)
		) );

		return $this->forceType($s, 's');
	}

	/**
	 * Check if there is an error in the connection
	 *
	 * @return boolean
	 */
	private function errorConnection() {
		return @mysqli_connect_errno();
	}

	/**
	 * You have to check the result before use this!
	 */
	private function fetch_row($fetch) {
		if($fetch === 'object' || $fetch === 'assoc') {
			return $this->lastResult->{'fetch_' . $fetch}();
		}
		error_die('Fetch row cannot be ' . $fetch);
	}

	public function affectedRows() {
		return @$this->mysqli->affected_rows();
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
			$this->numQueries,
			$SQL,
			esc_html($this->mysqli->error)
		);
	}

}
