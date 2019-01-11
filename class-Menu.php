<?php
# Copyright (C) 2015, 2019 Valerio Bozzolan
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
 * Handle a menu tree
 */
class Menu {
	private $entries = [];
	private $tree = [];
	private $rootUid;

	/**
	 * Specify the default root identifier.
	 *
	 * @param string $rootUid The default menu entry root identifier.
	 */
	function __construct( $rootUid = 'root' ) {
		$this->rootUid = $rootUid;
	}

	/**
	 * Get the singleton instance
	 *
	 * @return self
	 */
	public function instance() {
		static $me = false;
		if( ! $me ) {
			$me = new self();
		}
		return $me;
	}

	/**
	 * Append a single or an array of menu entries.
	 *
	 * @param array|MenuEntry $entries
	 */
	public function add( $entries ) {
		force_array( $entries );
		foreach( $entries as $menuEntry ) {
			$this->entries[ $menuEntry->uid ] = $menuEntry;
			force_array( $menuEntry->parentUid );
			foreach( $menuEntry->parentUid as $parentUid ) {
				if( $parentUid === null ) {
					$parentUid = $this->rootUid;
				}
				$this->setParent( $menuEntry->uid, $parentUid );
			}
		}
	}

	/**
	 * Force a parent->children relation.
	 *
	 * @param string $uid Children menu entry identifier.
	 * @param string $parentUid Parent menu entry identifier.
	 */
	public function setParent($uid, $parentUid) {
		if( ! isset( $this->tree[ $parentUid ] ) || ! is_array( $this->tree[ $parentUid ] ) ) {
			 $this->tree[ $parentUid ] = [];
		}
		$this->tree[ $parentUid ][] = $uid;
	}

	/**
	 * Get a single menu entry if registered
	 *
	 * @param string $uid Menu entry identifier
	 * @return MenuEntry
	 */
	public function getMenuEntry( $uid ) {
		if( isset( $this->entries[ $uid ] ) ) {
			return $this->entries[ $uid ];
		}
	}

	/**
	 * Get an array of menu entries.
	 *
	 * @param string|null $parentUid Parent menu entry identifier of the parent. The default menu entry root identifier as default.
	 * @return array
	 */
	public function getChildrenMenuEntries( $parentUid = null ) {
		if( $parentUid === null ) {
			$parentUid = $this->rootUid;
		}
		$entries = [];
		if( isset( $this->tree[ $parentUid ] ) ) {
			foreach( $this->tree[ $parentUid ] as $uid ) {
				$entries[] = $this->entries[ $uid ];
			}
		}
		return $entries;
	}
}
