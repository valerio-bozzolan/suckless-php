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

/**
 * Create a menu entry and define it's parent.
 */
class MenuEntry {
	/**
	 * Menu unique identifier.
	 * @type string
	 */
	public $uid;

	/**
	 * Identifier of the parent menu.
	 * @type array|string|null
	 */
	public $parentUid;

	/**
	 * Do whatever you want with this.
	 */
	public $url;
	public $name;
	public $extra;

	/**
	 * Create a menu entry.
	 */
	function __construct($uid, $url = null, $name = null, $parentUid = null, $extra = null) {
		$this->uid       = $uid;
		$this->url       = $url;
		$this->name      = $name;
		$this->parentUid = $parentUid;
		$this->extra     = $extra;
	}

	public function getExtra($arg, $default = null) {
		return $this->get($arg, $default);
	}

	public function get($arg, $default = null) {
		if( isset( $this->extra[$arg] ) ) {
			return $this->extra[$arg];
		}
		return $default;
	}
}
