<?php
# Copyright (C) 2015, 2016, 2017 Valerio Bozzolan
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

class DBCol {
	public $column;
	public $forceType;
	public $value;

	function __construct( $column, $value, $forceType ) {
		$this->column = $column;
		$this->value = $value;
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
	 * Empty strings can be promoted to NULL values.
	 */
	public function promoteNULL() {
		if( $this->forceType === 'snull' && ! $this->value ) {
			$this->value = null;
		}
	}

	public function isString() {
		return $this->forceType === 's' ||
		     ( $this->forceType === 'snull' && $this->value !== null );
	}
}

