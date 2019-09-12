<?php
# Copyright (C) 2015, 2016, 2017, 2019 Valerio Bozzolan
#
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program. If not, see <http://www.gnu.org/licenses/>.

/**
 * A database column sanification descriptor
 *
 * This class is useful to sanitize values before inserting them into the database
 */
class DBCol {

	public $column;

	public $forceType;

	public $value;

	/**
	 * Constructor
	 *
	 * @param $column string Name of the column e.g. 'user_ID'
	 * @param $value string Related value e.g. '1'
	 * @param $forceType string
	 * 	's'     string
	 *  'snull' string or NULL
	 *  'd'     integer
	 *  'dnull' integer or NULL
	 *  'f'     float
	 *  'fnull' float or NULL
	 *  '-'     no sanitization at ALL
	 */
	function __construct( $column, $value, $forceType ) {
		$this->column    = $column;
		$this->value     = $value;
		$this->forceType = $forceType;
	}

	public function getType() {
		return $this->forceType;
	}

	public function getValue() {
		return $this->value;
	}

	public function setValue( $value ) {
		return $this->value = $value;
	}

	/**
	 * Empty strings or zeroes can be promoted to NULL values.
	 */
	public function promoteNULL() {
		$type  = $this->forceType;
		$value = $this->value;
		if( 'snull' === $type && '' === $value || 'dnull' === $type && !$value ) {
			$this->value = null;
		}
	}

	/**
	 * Check if the value will be a string
	 *
	 * @return boolean
	 */
	public function isString() {
		return $this->forceType === 's' ||
		     ( $this->forceType === 'snull' && $this->value !== null );
	}

	/**
	 * Normalize a mixed array of DBCol and string/int values
	 *
	 * It's useful to allow syntax like:
	 *  	insertRow( [ 'name' => 'Mario' )
	 * Instead of:
	 *  	insertRow( [ new DBCol( 'name', 'mario', 's' ) ] );
	 *
	 * @return array An array of DBCol values
	 */
	public static function normalizeArray( $columns ) {

		// values to be returned
		$definitive = [];

		// check every user column
		foreach( $columns as $key => $value ) {

			if( !is_object( $value ) ) {

				// auto-guess type
				$type = 's';
				if( $value === null ) {
					$type = null;
				} elseif( is_int( $value ) ) {
					$type = 'd';
				}

				$value = new self( $key, $value, $type );
			}

			$definitive[] = $value;
		}
		return $definitive;
	}

}
