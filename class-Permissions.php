<?php
# Copyright (C) 2015, 2017 Valerio Bozzolan
#
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU General License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General License for more details.
#
# You should have received a copy of the GNU General License
# along with this program.  If not, see <http://www.gnu.org/licenses/>.

class Permissions {
	var $rolePermissions = [];

	public function __construct() {
		expect('session');
	}

	public function registerPermissions( $role, $permissions ) {
		force_array( $permissions );
		if( ! $this->roleExists( $role ) ) {
			$this->rolePermissions[ $role ] = [];
		}
		$this->rolePermissions[ $role ] = array_merge( $this->rolePermissions[ $role ], $permissions );
	}

	public function hasPermission( $role, $permission ) {
		if( $this->roleExists( $role ) ) {
			foreach( $this->rolePermissions[ $role ] as $rolePermission ) {
				if( $rolePermission === $permission ) {
					return true;
				}
			}
		}
		return false;
	}

	public function inheritPermissions( $new_role, $existing_role, $new_role_permissions = [] ) {
		if( ! $this->roleExists( $existing_role ) ) {
			self::errorRole( $existing_role );
			return false;
		}
		$this->registerPermissions( $new_role, $this->rolePermissions[ $existing_role ] );
		if( $new_role_permissions ) {
			$this->registerPermissions( $new_role, $new_role_permissions );
		}
		return true;
	}

	public function roleExists( $role ) {
		return isset( $this->rolePermissions[ $role ] );
	}

	public function getPermissions() {
		$all = [];
		foreach( $this->rolePermissions as $permissions ) {
			$all = array_merge( $all, $permissions );
		}
		return array_unique( $permissions );
	}

	public function getRoles() {
		return array_values( $this->rolePermissions );
	}

	public function clean() {
		foreach( $this->rolePermissions as $role => $permissions ) {
			$this->rolePermissions[ $role ] = array_unique( $permissions );
		}
	}

	private static function errorRole( $role ) {
		DEBUG && error( sprintf(
			_("Il ruolo %s non è stato ancora registrato"),
			esc_html( $role )
		) );
	}
}
