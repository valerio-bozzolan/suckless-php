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

class Permission {
	public $permission;

	function __construct($permission) {
		$this->permission = $permission;
	}

	public function __toString() {
		return $this->permission;
	}
}

class Permissions {
	private $db;

	public $rolePermissions = [];

	function __construct(& $db = null) {
		if($db === null) {
			expect('db');
			$this->db = & $GLOBALS['db'];
		} else {
			$this->db = $db;
		}
	}

	public function registerPermissions($role, $permissions) {
		if( !$this->roleExists($role) ) {
			$this->rolePermissions[ $role ] = array();
		}

		if( ! is_array($permissions) ) {
			$permissions = array($permissions);
		}

		$n = count($permissions);
		for($i=0; $i<$n; $i++) {
			$this->rolePermissions[ $role ][] = new Permission( $permissions[$i] );
		}
	}

	/**
	 * @deprecated
	 */
	public function registerPermission($role, $permissions) {
		$this->registerPermissions($role, $permissions);
	}

	public function hasPermission($role, $permission) {
		if( !$this->roleExists($role) ) {
			return false;
		}

		foreach($this->rolePermissions[ $role ] as $singlePermission) {
			if($singlePermission->permission == $permission) {
				return true;
			}
		}
		return false;
	}

	public function inheritPermissions($newRole, $existingRole) {
		if( !$this->roleExists($existingRole) ) {
			$this->errorRole($existingRole);
			return false;
		}
		if( !$this->roleExists($newRole) ) {
			$this->rolePermissions[ $newRole ] = array();
		}
		foreach($this->rolePermissions[ $existingRole ] as $permission) {
			$this->rolePermissions[ $newRole ][] = $permission;
		}
		return true;
	}

	public function roleExists($role) {
		return @is_array( $this->rolePermissions[ $role ] );
	}

	private function errorRole($role) {
		DEBUG && error( sprintf(
			_("Il ruolo %s non è stato ancora registrato"),
			esc_html( $role )
		) );
	}

	public function getPermissions() {
		$allPermissions = array();
		foreach($this->rolePermissions as $rolePermission) {
			foreach($rolePermission as $permission) {
				$allPermissions[] = $permission->permission;
			}
		}
		return array_unique($allPermissions);
	}

	public function getRoles() {
		$roles = array();
		foreach($this->rolePermissions as $role => $permission) {
			$roles[] = $role;
		}
		return array_unique($roles);
	}

	public function clean() {
		foreach( $this->rolePermissions as $role=>$permissions ) {
			$this->rolePermissions[ $role ] = array_unique( $permissions );
		}
	}
}
?>
