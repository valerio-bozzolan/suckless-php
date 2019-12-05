<?php
# Copyright (C) 2015, 2017, 2018 Valerio Bozzolan
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

// default anonymous user role
define_default( 'DEFAULT_USER_ROLE', 'UNREGISTERED' );

/**
 * Handle roles and their permissions
 */
class Permissions {

	/**
	 * Permissions indexed by user roles
	 */
	public $roles = [];

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->registerPermissions( DEFAULT_USER_ROLE, [] );
	}

	/**
	 * Get the singleton instance
	 */
	public static function instance() {
		static $me = false;
		if( ! $me ) {
			$me = new self();
		}
		return $me;
	}

	/**
	 * Register specific permissions to the wanted role
	 *
	 * @param $role string
	 * @param $permissions array
	 */
	public function registerPermissions( $role, $permissions ) {
		force_array( $permissions );
		if( ! $this->roleExists( $role ) ) {
			$this->roles[ $role ] = [];
		}
		$this->roles[ $role ] = array_merge( $this->roles[ $role ], $permissions );
	}

	/**
	 * Check if a role has a permission
	 *
	 * @param $role string
	 * @param $permissions string
	 * @return boolean
	 */
	public function hasPermission( $role, $permission ) {
		if( $this->roleExists( $role ) ) {
			return in_array( $permission, $this->roles[ $role ], true );
		} else {
			error( "unknown role $role" );
		}
		return false;
	}

	/**
	 * Check if you, or an user, has a permission
	 *
	 * @param $permission string
	 * @param $user object
	 * @return boolean
	 */
	public function userHasPermission( $permission, $user ) {

		$role = false;

		if( !$user ) {
			$user = Session::instance()->getUser();
		}

		if( $user ) {
			$role = $user->get( 'user_role' );
		}

		if( !$role ) {
			$role = DEFAULT_USER_ROLE;
		}

		return $this->hasPermission( $role, $permission );
	}

	/**
	 * Inherit permissions from an existing role, and extend it
	 *
	 * @param $new_role string
	 * @param $existing string
	 * @param $permissions array
	 */
	public function inheritPermissions( $new_role, $existing, $permissions = [] ) {
		if( ! $this->roleExists( $existing ) ) {
			throw new InvalidArgumentException( "unknown role $existing" );
		}
		$this->registerPermissions( $new_role, $this->roles[ $existing ] );
		$this->registerPermissions( $new_role, $permissions );
	}

	/**
	 * Check if a role formally exists
	 *
	 * @param $role string
	 * @return boolean
	 */
	public function roleExists( $role ) {
		return isset( $this->roles[ $role ] );
	}

	/**
	 * Get all the known permissions
	 *
	 * @return array
	 */
	public function getPermissions() {
		$all = [];
		foreach( $this->roles as $permissions ) {
			$all = array_merge( $all, $permissions );
		}
		return array_unique( $permissions );
	}

	/**
	 * Get all the roles
	 *
	 * @return array
	 */
	public function getRoles() {
		return array_keys( $this->roles );
	}

	/**
	 * Avoid duplicate permissions
	 *
	 * Well, it's only useful when you want to list all the permissions without duplicates.
	 *
	 * @return self
	 */
	public function clean() {
		foreach( $this->roles as $role => $permissions ) {
			$this->roles[ $role ] = array_unique( $permissions );
		}
		return $this;
	}
}
