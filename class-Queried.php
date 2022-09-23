<?php
# Copyright (C) 2017, 2018, 2019 Valerio Bozzolan
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
 * This class handles a database table result
 *
 * Very often this database table has an ID and an UID.
 */
class Queried {

	/**
	 * Maximum UID length
	 */
	const MAXLEN_UID = 256;

	/**
	 * Empty constructor
	 */
	public function __construct() {}

	/**
	 * Obtain a property that can be null (but can't be undefined).
	 *
	 * @param string $property
	 * @return mixed
	 */
	public function get( $property ) {
		if( property_exists( $this, $property ) ) {
			return $this->$property;
		}
		throw new SucklessException( sprintf(
			'cannot obtain %s->%s',
			get_class( $this ),
			$property
		) );
	}

	/**
	 * Check if a property is not NULL
	 *
	 * @param string $property
	 * @return bool
	 */
	public function has( $property ) {
		return null !== $this->get( $property );
	}

	/**
	 * Obtain a property that can't be null (and can't be undefined).
	 *
	 * @param string $property
	 * @return mixed
	 */
	public function nonnull($property) {
		if( isset( $this->$property ) ) {
			return $this->$property;
		}
		throw new SucklessException( sprintf(
			'cannot obtain %s->%s',
			get_class( $this ),
			$property
		) );
	}

	function integers() {
		foreach( func_get_args() as $p) {
			isset( $this->$p ) and
				$this->$p = (int) $this->$p;
		}
		return $this;
	}

	function booleans() {
		foreach( func_get_args() as $p) {
			isset( $this->$p ) and
				$this->$p = (bool) (int) $this->$p;
		}
		return $this;
	}

	function dates() {
		foreach( func_get_args() as $p) {
			isset( $this->$p ) and
				$this->$p = DateTime::createFromFormat('Y-m-d', $this->$p);
		}
	}

	function datetimes() {
		foreach( func_get_args() as $p) {
			isset( $this->$p ) and
				$this->$p = DateTime::createFromFormat('Y-m-d H:i:s', $this->$p);
		}
		return $this;
	}

	function floats() {
		foreach( func_get_args() as $p) {
			isset( $this->$p ) and
				$this->$p = (float) $this->$p;
		}
	}

	/**
	 * Query factory
	 *
	 * The class constant 'T' is expected as table name
	 *
	 * @return Query
	 */
	public static function factory() {
		return Query::factory( static::class )
			->from( static::T );
	}

	/**
	 * Factory from an ID column
	 *
	 * The class constant 'ID' is expected
	 *
	 * @param $ID int
	 * @return Queried
	 */
	public static function factoryFromID( $ID ) {
		$field = defined( static::class . '::ID' ) ? static::ID : static::T . '_ID';
		$field = static::T . DOT . $field;
		return static::factory()
			->whereInt( $field, $ID );
	}

	/**
	 * Factory from an UID column
	 *
	 * The class constant 'UID' is expected
	 *
	 * @param $uid string
	 * @return Queried
	 */
	public static function factoryFromUID( $uid ) {
		$field = defined( static::class . '::UID' ) ? static::UID : static::T . '_uid';
		$uid = static::sanitizeUID( $uid );
		return static::factory()
			->whereStr( $field, $uid );
	}

	/**
	 * Sanitize the UID
	 *
	 * @return string
	 * @use luser_input()
	 */
	public static function sanitizeUID( $uid ) {
		return luser_input( $uid, static::MAXLEN_UID );
	}

	/**
	 * Alias of self::factoryFromID()
	 *
	 * @deprecated
	 */
	public static function factoryByID( $ID ) {
		return self::factoryFromID( $ID );
	}

	/**
	 * Alias of self::factoryFromUID()
	 *
	 * @deprecated
	 */
	public static function factoryByUID( $uid ) {
		return self::factoryFromUID( $uid );
	}
}
