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

/**
 * Site associative options handled by a specific database table (if you use it)
 */
class Options {

 	/**
	 * Cached options
	 * @var array
	 */
	private $cache = [];

	/**
	 * List of formally registered options.
	 * @var array
	*/
	private $opts = [];

	/**
	 * Get the singleton instance
	 *
	 * @return self
	 */
	public static function instance() {
		static $me = false;
		if( ! $me ) {
			$me = new self();
		}
		return $me;
	}

	/**
	 * Load options with autoload in cache
	 */
	public function autoload() {
		static $todo = true;
		if( $todo ) {
			$options = Query::factory()
				->select( [
					'option_name',
					'option_value',
				] )
				->from( 'option' )
				->whereInt( 'option_autoload', 1 )
				->queryResults();

			foreach( $options as $option ) {
				$this->override( $option->option_name, $option->option_value );
			}
			$todo = false;
		}
	}

	/**
	 * Formally register an option and know if it's already registered
	 *
	 * @param string $name Option name
	 * @return bool Successfully or not
	 */
	public function register( $name ) {
		if( in_array( $name, $this->opts, true) ) {
			return error( "error registering the option $name because it is already registered" );
		}
		$this->opts[] = $name;
		return true;
	}

	/**
	 *	Get all formally registered options
	 *
	 *	@return array
	 */
	public function getRegistereds() {
		return $this->opts;
	}

	/**
	 * Get the value of an option
	 *
	 * @param string $name Option name
	 * @param string $defalut Default option value
	 */
	public function get( $name, $default = '' ) {
		$value = '';
		$this->autoload();
		if( array_key_exists( $name, $this->cache ) ) {
			$value = $this->cache[ $name ];
		} else {
			$value = Query::factory()
				->from( 'option' )
				->whereStr( 'option_name', $name )
				->queryValue( 'option_value' );

			$this->override( $name, $value );
		}
		return empty( $value ) ? $default : $value;
	}

	/**
 	 * Set an option in the cache in order to override the database value
	 *
	 * @param string $name Option name
	 * @param string $value Option value
	 */
	public function override( $name, $value ) {
		$this->cache[ $name ] = $value;
	}

	/**
	 * Set the value of an option into the database (updating or inserting) and cache
	 *
	 * @param string $name Option name
	 * @param string $value Option value
	 * @param true $autoload If the option it's automatically requested on every page-request
	 */
	public function set( $name, $value, $autoload = true ) {
		$this->override( $name, $value );

		$autoload = $autoload ? 1 : 0;
		insert_row( 'option', [
			new DBCol( 'option_name',     $name,     's' ),
			new DBCol( 'option_value',    $value,    's' ),
			new DBCol( 'option_autoload', $autoload, 'd' ),
		], [ 'replace-into' => true ] );
	}

	/**
	 * Permanently remove an option from the database
	 *
	 * @param string $name Option name
	 */
	public function remove( $name ) {
		query( sprintf(
			"DELETE FROM %s WHERE option_name LIKE '%s' LIMIT 1",
			T( 'option' ),
			esc_sql( $name )
		) );
		$this->override( $name, null );
	}

}
