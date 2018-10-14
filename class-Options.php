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

/**
 * Site associative options handled by a specific database table (if you use it)
 */
class Options {

 	/**
	 * Cached options
	 * @var array
	 */
	private $cache = null;

	/**
	 * List of formally registered options.
	 * @var array
	*/
	private $optionsRegistered = [];

	/**
	 * Get the instance
	 */
	public static function instance() {
		$self = null;
		if( ! $self ) {
			$self = new self();
		}
		return $self;
	}

	/**
	 * Load options with autoload in cache
	 */
	public function autoload() {
		if( $this->cache === null ) {
			$options = Query::factory()
				->select( [
					'option_name',
					'option_value',
				] )
				->from( 'option' )
				->where( 'option_autoload != 0' )
				->queryResults();

			foreach( $options as $option ) {
				$this->cache[ $option->option_name ] = $option->option_value;
			}
		}
	}

	/**
	 * Formally register an option and know if it's already registered
	 *
	 * @param string $name Option name
	 * @return bool Successfully or not
	 */
	public function register( $name ) {
		if( in_array( $name, $this->optionsRegistered, true) ) {
			DEBUG and error( sprintf(
				'error registering the option "%s" because it is already registered',
				$name
			) );
			return false;
		}
		$this->optionsRegistered[] = $name;
		return true;
	}

	/**
	 *	Get all formally registered options
	 *
	 *	@return array
	 */
	public function getRegistereds() {
		return $this->optionsRegistered;
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
		if( isset( $this->cache[ $name ] ) ) {
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
		// do not autoload here. 'get' autoloads, 'set' nope
		$this->override( $name, $value );
		if( isset( $this->cache[ $name ] ) ) {
			if( $this->cache[ $name ] !== $value ) {
				$autoload = $autoload ? 1 : 0;
				query_update( 'option', [
						new DBCol( 'option_value',    $value,    's' ),
						new DBCol( 'option_autoload', $autoload, 'd' ),
					],
					sprintf( 'option_name = %s', esc_sql( $name ) )
				);
			}
		} else {
			// here the option does not exist or was not autoloaded (so it exists)
			insert_row( 'option', [
				new DBCol( 'option_name',     $name,     's' ),
				new DBCol( 'option_value',    $value,    's' ),
				new DBCol( 'option_autoload', $autoload, 'd' ),
			], [ 'replace-into' => true ] );
		}
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
