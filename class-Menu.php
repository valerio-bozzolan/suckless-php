<?php
/*
 * Copyright (C) 2015 Valerio Bozzolan
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

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

/**
 * Handle a menu tree.
 */
class Menu {
	private $menuEntries = [];
	private $tree = [];
	private $rootUid;

	/**
	 * Specify the default root identifier.
	 *
	 * @param string $rootUid The default menu entry root identifier.
	 */
	function __construct($rootUid = 'root') {
		$this->rootUid = $rootUid;
	}

	/**
	 * Append a single or an array of menu entries.
	 *
	 * @param array|MenuEntry $menuEntries
	 */
	public function add($menuEntries) {
		if( ! array($menuEntries) ) {
			$menuEntries = array($menuEntries);
		}

		foreach($menuEntries as $menuEntry) {
			$this->menuEntries[ $menuEntry->uid ] = $menuEntry;

			if( ! is_array($menuEntry->parentUid) ) {
				$menuEntry->parentUid = array($menuEntry->parentUid);
			}
			foreach($menuEntry->parentUid as $parentUid) {
				if( $parentUid === null ) {
					$parentUid = $this->rootUid;
				}
				$this->setParent($menuEntry->uid, $parentUid);
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
			 $this->tree[ $parentUid ] = array();
		}

		$this->tree[ $parentUid ][] = $uid;
	}

	/**
	 * Get a single menu entry.
	 *
	 * @param string $uid Menu entry identifier.
	 * @return MenuEntry
	 */
	public function getMenuEntry($uid) {
		if( isset( $this->menuEntries[ $uid ] ) ) {
			return $this->menuEntries[ $uid ];
		}

		DEBUG && error( sprintf(
			_("La voce di menu '%s' non è stata ancora creata."),
			esc_html($uid)
		) );

		return null;
	}

	/**
	 * Get an array of menu entries.
	 *
	 * @param string|null $parentUid Parent menu entry identifier of the parent. The default menu entry root identifier as default.
	 * @return array
	 */
	public function getChildrenMenuEntries($parentUid = null) {
		if($parentUid === null) {
			$parentUid = $this->rootUid;
		}

		$menuEntries = array();

		if( isset( $this->tree[ $parentUid ] ) ) {
			foreach($this->tree[ $parentUid ] as $uid) {
				$menuEntries[] = $this->menuEntries[ $uid ];
			}
		}

		return $menuEntries;
	}
}

