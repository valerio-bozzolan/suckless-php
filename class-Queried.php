<?php
# Copyright (C) 2017 Valerio Bozzolan
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
 * This class is intended to be extended by injectable classes.
 */
class Queried {
	/**
	 * Obtain a property that can be null (but can't be undefined).
	 *
	 * @param string $property
	 */
	function nullable($property) {
		if( property_exists($this, $property) ) {
			return $this->$property;
		}

		error_die( sprintf(
			_("Impossibile ottenere %s->%s"),
			__CLASS__,
			$property
		) );
	}

	/**
	 * Obtain a property that can't be null (and can't be undefined).
	 *
	 * @param string $property
	 */
	function nonnull($property) {
		if( isset( $this->$property ) ) {
			return $this->$property;
		}

		error_die( sprintf(
			_("Impossibile ottenere %s->%s"),
			__CLASS__,
			$property
		) );
	}

	function integers(...$properties) {
		foreach($properties as $property) {
			isset( $this->$property ) and
				$this->$property = (int) $this->$property;
		}
		return $this;
	}

	function booleans(...$properties) {
		foreach($properties as $property) {
			isset( $this->$property ) and
				$this->$property = (bool) (int) $this->$property;
		}
		return $this;
	}

	function datetimes(...$properties) {
		foreach($properties as $property) {
			isset( $this->$property ) and
				$this->$property = DateTime::createFromFormat('Y-m-d H:i:s', $s);
		}
		return $this;
	}
}
