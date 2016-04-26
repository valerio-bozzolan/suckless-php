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

/*
 * Use: esc_html(), error_die(), DEBUG, merge_args_defaults(), force_array()
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
	 * Cached options
	 * @var array
	 */
	private $optionsCache = null;

	/**
	 * List of formally registered options.
	 * @var array
	*/
	private $optionsUsed = [];

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
				$password_shown = ($password === '') ? _("nessuna") : sprintf(
					_("di %d caratteri"),
					strlen( $password )
				);

				error_die( sprintf(
					_("Impossibile connettersi al database '%s' tramite l'utente '%s' e password (%s) sul server MySQL/MariaDB '%s'. Specifica correttamente queste informazioni nel file di configurazione del tuo progetto (usualmente '%s'). %s."),
					$database,
					$username,
					$password_shown,
					$location,
					'load.php',
					HTML::a(
						'https://github.com/valerio-bozzolan/boz-php-another-php-framework/blob/master/README.md#use-it',
						_("Documentazione")
					)
				) );
			} else {
				error_die( _("Errore nello stabilire una connessione al database.") );
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
			_("Query numero %d: <pre>%s</pre>"),
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
			$class_name = 'EhmStdClass';
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
		$args = merge_args_defaults($args, [
			'replace-into' => false
		] );

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
					_("Errore inserendo nella tabella <em>%s</em>. Colonne: <em>%d</em>. Colonne values[<em>%d</em>]: <em>%d</em>"),
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
	 * Load options with autoload.
	 */
	public function loadAutoloadOptions() {
		$options = $this->getResults("SELECT option_name, option_value FROM {$this->getTable('option')} WHERE option_autoload != 0");
		if($options === false) {
			if(DEBUG) {
				error_die( _("Errore caricando le opzioni dal database") );
			} else {
				error_die( sprintf(
					_("Probabilmente la tabella %s non esiste."),
					$this->getTable('option')
				) );
			}
		}

		$n = count($options);
		for($i=0; $i<$n; $i++) {
			$this->optionsCache[ $options[$i]->option_name ] = $options[$i]->option_value;
		}

		return $n;
	}

	/**
	 * Formally register an option_name (and know if it's already registered
	 *  by another piece of code).
	 * This function is useful to don't override previous options registred by other plugins or themes,
	 * and it's very useful to know what options can be removed from the database.
	 *
	 * @param string $option_name Option name
	 * @return bool Successfully or not.
	 */
	public function registerOption($option_name, $wildcard = false) {
		if( in_array($option_name, $this->optionsUsed, true) ) {
			DEBUG && error( sprintf(
				_("Errore registrando l'opzione '%s' poichè risulta già registrata!"),
				esc_html($option_name)
			) );
			return false;
		}
		if($wildcard) {
			$option_name .= '*';
		}
		$this->optionsUsed[] = $option_name;
		return true;
	}

	/**
	 *	Know used options.
	 *
	 *	@return array Options used.
	*/
	public function getOptionsUsed() {
		return $this->optionsUsed;
	}

	/**
	 * Get the value of an option.
	 *
	 * @param string $option_name Option name.
	 * @param string $defalut_value Default value if this option does not exist.
	 */
	public function getOption($option_name, $default_value = '') {
		if( $this->optionsCache === null ) {
			$this->loadAutoloadOptions();
		}

		if(isset($this->optionsCache[$option_name])) {
			return (empty($this->optionsCache[$option_name])) ? $default_value : $this->optionsCache[$option_name];
		} else {
			$option = $this->get_row( sprintf(
				"SELECT * FROM {$this->getTable('option')} WHERE option_name='%s'",
				$this->escapeString($option_name)
			) );
			if(empty($option->option_value)) {
				$this->optionsCache[ $option_name ] = '';
				return $default_value;
			} else {
				return $this->optionsCache[ $option->option_name ] = $option->option_value;
			}
		}
	}

	/**
 	 * Set an option in the cache in order to override the database value
	 * @param string $option_name Option name
	 * @param string $option_value Option value
	 */
	public function overrideOption($option_name, $option_value) {
		$this->optionsCache[$option_name] = $option_value;
	}

	/**
	 * Set the value of an option.
	 *
	 * @param string $option_name Option name
	 * @param string $option_value Option value
	 * @param true $autoload If the option it's automatically requested on every page-request
	 */
	public function setOption($option_name, $option_value, $option_autoload = true) {
		$option_value = trim($option_value);
		if(isset($this->optionsCache[ $option_name ])) {
			if($this->optionsCache[ $option_name ] != $option_value) {
				$this->optionsCache[$option_name] = $option_value;

				$option_autoload = ($option_autoload) ? 1 : 0;

				$this->update('option', [
						new DBCol('option_value',    $option_value,    's'),
						new DBCol('option_autoload', $option_autoload, 'd')
					],
					"option_name = '{$this->escapeString($option_name)}'"
				);
			}
		} else {
			return $this->insertOption($option_name, $option_value, $option_autoload);
		}
	}

	/**
	 * Insert a new option.
	 *
	 * @param string $option_name Option name
	 * @param string $option_value Option value
	 * @param boolean $option_autoload If the option it's automatically requested on every page-request
	 * @return boolean Sucessfully or not
	 * @TODO 'replace-into' => This is not OK
	 */
	private function insertOption($option_name, $option_value, $option_autoload = true) {
		if( $this->optionsCache === null ) {
			$this->loadAutoloadOptions();
		}

		if(isset($this->optionsCache[$option_name])) {
			return $this->setOption($option_name, $option_value);
		} else {
			$option_autoload = ($option_autoload) ? 1 : 0;

			$this->insert(
				'option', [
					'option_name' => 's',
					'option_value' => 's',
					'option_autoload' => 's' // Enum
				], [
					$option_name,
					$option_value,
					$option_autoload
				], [
					'replace-into' => true
				]
			);
			if( ! $this->lastResult ) {
				return false;
			}

			$this->optionsCache[$option_name] = $option_value;
			return true;
		}
	}

	/**
	 * Permanently remove an option.
	 *
	 * @param string $option_name Option name.
	 * @return boolean Succesfully or not.
	 */
	public function removeOption($option_name) {
		$this->query( sprintf(
			"DELETE FROM {$this->getTable('option')} WHERE option_name = '%s' LIMIT 1",
			$this->escapeString($option_name)
		));

		if( $this->optionsCache !== null ) {
			unset($this->optionsCache[$option_name]);
		}
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
		$tables = '';
		if( ! is_array($args) ) {
			$args = func_get_args();
		}
		if($n = count($args)) {
			for($i=0; $i<$n; $i++) {
				if($i !== 0) {
					$tables .= ', ';
				}
				$tables .= $this->getTable($args[$i], true);
			}
		}
		return $tables;
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
			_("Errore eseguendo una query SQL: Query n. %d: <blockquote><pre>%s</pre></blockquote><br />Errore: <pre>%s</pre>"),
			$this->numQueries,
			$SQL,
			esc_html($this->mysqli->error)
		);
	}

	/**
	 * To get a single row from a query.
	 *
	 * @deprecated
	 * @param $SQL SQL query
	 * @param $args Arguments.
	 * @return object Object row.
	 */
	public function get_row($SQL) {
		return $this->getRow($SQL);
	}

	/**
	 * Return multi-row from a query.
	 *
	 * @deprecated
	 * @param string $SQL The SQL query to execute
	 * @param array $args Arguments.
	 * @return array The result is as an array of object.
	 */
	public function get_results($SQL) {
		return $this->getResults($SQL);
	}

	/**
	 * @deprecated
 	 */
	public function get_last_inserted_id() {
		return $this->mysqli->insert_id;
	}

	/**
	 *@deprecated
	 */
	public function get_table($name, $as = false) {
		return $this->getTable($name, $as);
	}

	/**
	 * @deprecated
	 */
	public function get_tables($args = []) {
		if( ! is_array($args) ) {
			$args = func_get_args();
		}
		return $this->getTables($args);
	}
}

class DBCol {
	public $column;
	public $forceType;
	public $value;

	function __construct($column, $value, $forceType) {
		$this->column = $column;
		$this->value = $value;
		$this->forceType = $forceType;
	}
}

/**
 * The stdClass does not have a constructor.
 *
 * @See http://php.net/manual/en/mysqli-result.fetch-object.php
 */
class EhmStdClass {
	function __construct() {
	}
}
